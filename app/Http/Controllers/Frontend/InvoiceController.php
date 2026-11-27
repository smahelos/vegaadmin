<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Application\Invoice\Commands\CopyInvoice;
use App\Application\Invoice\Commands\CreateGuestInvoice;
use App\Application\Invoice\Commands\CreateInvoice;
use App\Application\Invoice\Commands\DeleteGuestInvoice;
use App\Application\Invoice\Commands\DeleteInvoice;
use App\Application\Invoice\Commands\SetInvoiceStatus;
use App\Application\Invoice\Commands\SetInvoiceTemplate;
use App\Application\Invoice\Commands\UpdateInvoice;
use App\Application\Invoice\Contracts\InvoiceRequestAssemblerInterface;
use App\Application\Invoice\DTO\InvoiceFormDTO;
use App\Application\Invoice\DTO\PdfInvoiceDTO;
use App\Application\Invoice\Presenters\InvoiceDetailPresenter;
use App\Application\Invoice\Presenters\InvoiceFormPresenter;
use App\Application\Invoice\Presenters\InvoiceStatusPresenter;
use App\Application\Invoice\Queries\GetGuestInvoicePdf;
use App\Application\Invoice\Queries\GetInvoice;
use App\Application\Invoice\Queries\GetInvoiceCreateForm;
use App\Application\Invoice\Queries\GetInvoiceForEdit;
use App\Application\Invoice\Queries\GetInvoicePdf;
use App\Application\Invoice\Services\GuestInvoiceCreateForm;
use App\Application\Invoice\Services\InvoiceProductWriteDataAssembler;
use App\Application\Shared\Geography\Presenters\CountryOptionsPresenter;
use App\Application\Shared\Translation\Presenters\LanguageOptionsPresenter;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Domain\Invoice\Exceptions\InvoiceIssuedException;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\User\ValueObjects\UserId;
use App\Http\Controllers\Controller;
use App\Http\Requests\Invoice\SetInvoiceTemplateRequest;
use App\Http\Requests\InvoiceRequest;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\HeaderUtils;

/**
 * Every action runs one use case through the gate; the routes name the same use cases in `allowed`, so the two
 * change together.
 */
final class InvoiceController extends Controller
{
    public function __construct(
        private readonly AuthorizingUseCaseRunnerInterface $useCases,
        private readonly InvoiceRequestAssemblerInterface $invoiceRequestAssembler,
        private readonly CountryOptionsPresenter $countries,
        private readonly InvoiceStatusPresenter $statuses,
        private readonly InvoiceDetailPresenter $details,
        private readonly InvoiceFormPresenter $forms,
        private readonly InvoiceProductWriteDataAssembler $products,
        private readonly LanguageOptionsPresenter $languages,
    ) {}

    public function index(): View
    {
        return view('frontend.invoices.index');
    }

    public function create(Request $request): View
    {
        $owner = $this->owner();
        $businessId = $request->integer('business_id') ?: null;
        $form = $this->useCases->run(
            Actor::user($owner),
            new GetInvoiceCreateForm(
                $owner,
                $businessId === null ? null : BusinessId::fromInt($businessId),
            ),
        );

        return view(
            'frontend.invoices.create',
            [
                ...$this->formView($form),
                'contactId' => $request->integer('contact_id') ?: null,
                'businessId' => $businessId,
            ],
        );
    }

    public function createForGuest(GuestInvoiceCreateForm $guestForm): View
    {
        $form = $guestForm->read();

        return view(
            'frontend.invoices.create',
            $this->formView($form),
        );
    }

    public function edit(string $locale, int $id): View|RedirectResponse
    {
        $owner = $this->owner();
        try {
            $editable = $this->useCases->run(
                Actor::user($owner),
                new GetInvoiceForEdit(
                    $owner,
                    InvoiceId::fromInt($id),
                ),
            );
        } catch (InvoiceIssuedException) {
            // The detail shows an issued invoice read-only with the one change it allows, the payment status
            $redirector = redirect();
            $redirect = $redirector->route(
                'frontend.invoice.show',
                ['locale' => $locale, 'id' => $id],
            );

            return $redirect->with(
                'error',
                __('invoices.messages.issued_locked'),
            );
        }
        $invoice = $editable->invoice;

        return view(
            'frontend.invoices.edit',
            [
                ...$this->formView($editable->form),
                'invoice' => $invoice,
                'values' => $this->forms->values($invoice),
                'items' => $this->forms->items($invoice),
            ],
        );
    }

    public function store(InvoiceRequest $request): RedirectResponse
    {
        $owner = $this->owner();
        $invoice = $this->useCases->run(
            Actor::user($owner),
            new CreateInvoice(
                $owner,
                $request->toWriteData(),
                $request->partySelection(),
                $request->products($this->products),
            ),
        );

        // The detail offers the preview PDF and issuing of the new draft
        $redirector = redirect();
        $redirect = $redirector->route(
            'frontend.invoice.show',
            ['locale' => App::getLocale(), 'id' => $invoice->id->value],
        );

        return $redirect->with(
            'success',
            __('invoices.messages.created'),
        );
    }

    public function storeGuest(InvoiceRequest $request): JsonResponse
    {
        $payload = $this->invoiceRequestAssembler->assemble($request);
        $result = $this->useCases->run(
            Actor::anonymous(),
            new CreateGuestInvoice(
                $payload->validated,
                $request->oneOffParty('supplier'),
                $request->oneOffParty('customer'),
                $request->oneOffAccount(),
                $request->get('lang'),
            ),
        );

        $session = session();
        $session->put('last_guest_invoice_token', $result->token);
        $session->put('last_guest_invoice_number', $result->number);
        $now = CarbonImmutable::now();
        $expiration = $now->addMinutes(10);
        $session->put('last_guest_invoice_expires', $expiration->timestamp);

        $responseFactory = response();

        return $responseFactory->json(
            [
                'success' => true,
                'message' => __('invoices.messages.created_guest'),
                'invoice_number' => $result->number,
                'download_url' => route(
                    'frontend.invoice.download.token',
                    ['locale' => $result->locale, 'token' => $result->token],
                ),
                'token' => $result->token,
                'qr_code' => $result->qrCode,
                'has_qr_code' => $result->qrCode !== null && $result->qrCode !== '',
            ],
        );
    }

    public function show(
        string $locale,
        int $id,
        Request $request,
    ): View {
        $owner = $this->owner();
        $detail = $this->useCases->run(
            Actor::user($owner),
            new GetInvoice(
                $owner,
                InvoiceId::fromInt($id),
            ),
        );
        $invoice = $detail->invoice;

        return view(
            'frontend.invoices.show',
            [
                'detail' => $detail,
                'invoice' => $invoice,
                'parties' => $invoice->parties,
                'presenter' => $this->details,
                'statusColor' => $this->statuses->color($invoice->issue === null ? null : $invoice->status),
                'countries' => $this->countries->options($locale),
                'openIssueDialog' => $request->boolean('issue'),
            ],
        );
    }

    public function download(
        string $locale,
        int $id,
        Request $request,
    ): Response {
        $owner = $this->owner();
        $pdf = $this->useCases->run(
            Actor::user($owner),
            new GetInvoicePdf(
                $owner,
                InvoiceId::fromInt($id),
            ),
        );

        return $this->pdfResponse($pdf, $request);
    }

    public function downloadWithToken(
        Request $request,
        string $locale,
        string $token,
    ): Response {
        $pdf = $this->useCases->run(
            Actor::anonymous(),
            new GetGuestInvoicePdf(
                $token,
                $request->get('lang'),
            ),
        );
        $response = $this->pdfResponse($pdf, $request);

        return $response->cookie(
            'locale',
            $pdf->locale,
            60 * 24 * 30,
        );
    }

    public function update(
        InvoiceRequest $request,
        string $locale,
        int $id,
    ): RedirectResponse {
        $owner = $this->owner();
        $invoice = $this->useCases->run(
            Actor::user($owner),
            new UpdateInvoice(
                $owner,
                InvoiceId::fromInt($id),
                $request->toWriteData(),
                $request->partySelection(),
                $request->products($this->products),
            ),
        );

        $parameters = ['id' => $invoice->id->value, 'locale' => $locale];
        // "Save and issue": the detail opens the issue dialog of the saved draft
        if ($request->boolean('issue')) {
            $parameters['issue'] = 1;
        }
        $redirector = redirect();
        $redirect = $redirector->route(
            'frontend.invoice.show',
            $parameters,
        );

        return $redirect->with(
            'success',
            __('invoices.messages.updated'),
        );
    }

    public function destroyGuest(string $locale, string $token): RedirectResponse
    {
        $session = session();
        $redirector = redirect();
        $redirect = $redirector->route('home', ['locale' => $locale]);
        try {
            $this->useCases->run(
                Actor::anonymous(),
                new DeleteGuestInvoice(
                    $token,
                    $session->get('last_guest_invoice_token'),
                ),
            );
        } catch (InvoiceNotFoundException) {
            return $redirect->with(
                'error',
                __('invoices.messages.token_invalid'),
            );
        }
        $session->forget(
            [
                'last_guest_invoice_token',
                'last_guest_invoice_number',
                'last_guest_invoice_expires',
            ],
        );

        return $redirect->with(
            'success',
            __('invoices.messages.deleted_guest'),
        );
    }

    public function copy(string $locale, int $invoice): RedirectResponse
    {
        $owner = $this->owner();
        $draft = $this->useCases->run(
            Actor::user($owner),
            new CopyInvoice(
                $owner,
                InvoiceId::fromInt($invoice),
            ),
        );

        $redirector = redirect();
        $redirect = $redirector->route(
            'frontend.invoice.edit',
            ['locale' => $locale, 'id' => $draft->id->value],
        );

        return $redirect->with(
            'success',
            __('invoices.messages.copied'),
        );
    }

    public function destroy(string $locale, int $invoice): RedirectResponse
    {
        $owner = $this->owner();
        $this->useCases->run(
            Actor::user($owner),
            new DeleteInvoice(
                $owner,
                InvoiceId::fromInt($invoice),
            ),
        );

        $redirector = redirect();
        $redirect = $redirector->route(
            'frontend.invoices',
            ['locale' => $locale],
        );

        return $redirect->with(
            'success',
            __('invoices.messages.deleted'),
        );
    }

    public function setStatus(
        string $locale,
        int $invoice,
        InvoiceStatus $status,
    ): RedirectResponse {
        $owner = $this->owner();
        $this->useCases->run(
            Actor::user($owner),
            new SetInvoiceStatus(
                $owner,
                InvoiceId::fromInt($invoice),
                $status,
            ),
        );

        $redirect = back();

        return $redirect->with(
            'success',
            __('invoices.messages.status_set'),
        );
    }

    public function setTemplate(
        SetInvoiceTemplateRequest $request,
        string $locale,
        int $id,
    ): RedirectResponse {
        $owner = $this->owner();
        $this->useCases->run(
            Actor::user($owner),
            new SetInvoiceTemplate(
                $owner,
                InvoiceId::fromInt($id),
                $request->template(),
            ),
        );

        $redirector = redirect();
        $redirect = $redirector->route(
            'frontend.invoice.edit',
            ['locale' => $locale, 'id' => $id],
        );

        return $redirect->with(
            'success',
            __('invoices.messages.template_set'),
        );
    }

    private function pdfResponse(PdfInvoiceDTO $pdf, Request $request): Response
    {
        $disposition = HeaderUtils::makeDisposition(
            $request->has('preview') ? HeaderUtils::DISPOSITION_INLINE : HeaderUtils::DISPOSITION_ATTACHMENT,
            $pdf->filename,
        );

        return new Response(
            $pdf->content,
            200,
            ['Content-Type' => 'application/pdf', 'Content-Disposition' => $disposition],
        );
    }

    private function owner(): UserId
    {
        return UserId::fromInt((int) Auth::id());
    }

    /** @return array<string, mixed> */
    private function formView(InvoiceFormDTO $form): array
    {
        return [
            'fields' => $form->fields->toArray(),
            'paymentMethods' => $form->paymentMethods,
            'taxRates' => $form->taxRates,
            'userLoggedIn' => $form->userLoggedIn,
            'suggestedNumber' => $form->suggestedNumber,
            'languages' => $this->languages->options(App::getLocale()),
            'documentLanguage' => $form->documentLanguage?->value ?? App::getLocale(),
        ];
    }
}
