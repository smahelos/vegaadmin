<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Application\Invoice\Commands\CreateGuestInvoice;
use App\Application\Invoice\Commands\CreateInvoice;
use App\Application\Invoice\Commands\DeleteGuestInvoice;
use App\Application\Invoice\Commands\MarkInvoicePaid;
use App\Application\Invoice\Commands\SetInvoiceTemplate;
use App\Application\Invoice\Commands\UpdateInvoice;
use App\Application\Invoice\Contracts\InvoiceRequestAssemblerInterface;
use App\Application\Invoice\DTO\PdfInvoiceDTO;
use App\Application\Invoice\Queries\GetGuestInvoicePdf;
use App\Application\Invoice\Queries\GetInvoice;
use App\Application\Invoice\Queries\GetInvoiceCreateForm;
use App\Application\Invoice\Queries\GetInvoiceForEdit;
use App\Application\Invoice\Queries\GetInvoicePdf;
use App\Application\Invoice\Services\GuestInvoiceCreateForm;
use App\Application\Shared\Geography\Presenters\CountryOptionsPresenter;
use App\Application\Shared\UseCase\Actor;
use App\Application\Shared\UseCase\Contracts\AuthorizingUseCaseRunnerInterface;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Invoice\ValueObjects\InvoiceId;
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
    ) {}

    public function index(): View
    {
        return view('frontend.invoices.index');
    }

    public function create(Request $request): View
    {
        $owner = $this->owner();
        $form = $this->useCases->run(
            Actor::user($owner),
            new GetInvoiceCreateForm($owner),
        );

        return view(
            'frontend.invoices.create',
            [
                ...$form->toArray(),
                'contactId' => $request->integer('contact_id') ?: null,
                'businessId' => $request->integer('business_id') ?: null,
            ],
        );
    }

    public function createForGuest(GuestInvoiceCreateForm $guestForm): View
    {
        $form = $guestForm->read();

        return view('frontend.invoices.create', $form->toArray());
    }

    public function edit(string $locale, int $id): View
    {
        $owner = $this->owner();
        $form = $this->useCases->run(
            Actor::user($owner),
            new GetInvoiceForEdit(
                $owner,
                InvoiceId::fromInt($id),
            ),
        );

        return view('frontend.invoices.edit', $form->toArray());
    }

    public function store(InvoiceRequest $request): RedirectResponse
    {
        $owner = $this->owner();
        $this->useCases->run(
            Actor::user($owner),
            new CreateInvoice(
                $owner,
                $request->toWriteData(),
                $request->partySelection(),
                $request->products(),
            ),
        );

        $redirector = redirect();
        $redirect = $redirector->route(
            'frontend.invoices',
            ['locale' => App::getLocale()],
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

    public function show(string $locale, int $id): View
    {
        $owner = $this->owner();
        $invoice = $this->useCases->run(
            Actor::user($owner),
            new GetInvoice(
                $owner,
                InvoiceId::fromInt($id),
            ),
        );

        return view(
            'frontend.invoices.show',
            [
                ...$invoice->toArray(),
                'countries' => $this->countries->options($locale),
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
                $request->get('lang'),
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

        return $this->pdfResponse($pdf, $request);
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
                $request->products(),
            ),
        );

        $redirector = redirect();
        $redirect = $redirector->route(
            'frontend.invoice.show',
            ['id' => $invoice->id->value, 'locale' => $locale],
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

    public function markAsPaid(string $locale, int $id): RedirectResponse
    {
        $owner = $this->owner();
        $this->useCases->run(
            Actor::user($owner),
            new MarkInvoicePaid(
                $owner,
                InvoiceId::fromInt($id),
            ),
        );

        $redirect = back();

        return $redirect->with(
            'success',
            __('invoices.messages.marked_as_paid'),
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
        $response = new Response(
            $pdf->content,
            200,
            ['Content-Type' => 'application/pdf', 'Content-Disposition' => $disposition],
        );

        return $response->cookie(
            'locale',
            $request->get('lang') ?? App::getLocale(),
            60 * 24 * 30,
        );
    }

    private function owner(): UserId
    {
        return UserId::fromInt((int) Auth::id());
    }
}
