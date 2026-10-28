<?php

namespace App\Http\Controllers\Frontend;

use App\Application\Invoice\Contracts\GuestInvoiceApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceFormApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceFrontendActionsApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceMutationApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoicePdfApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceRequestAssemblerInterface;
use App\Application\Invoice\DTO\InvoiceActionStatus;
use App\Application\Invoice\DTO\OrchestratedInvoiceActions;
use App\Application\Shared\Geography\Presenters\CountryOptionsPresenter;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    /**
     * Constructor – property promotion keeps controller thin and immutable.
     */
    public function __construct(
        private readonly InvoiceFormApplicationServiceInterface $invoiceFormService,
        private readonly GuestInvoiceApplicationServiceInterface $guestInvoiceApplicationService,
        private readonly InvoiceFrontendActionsApplicationServiceInterface $invoiceFrontendActions,
        private readonly InvoiceRequestAssemblerInterface $invoiceRequestAssembler,
        private readonly InvoiceMutationApplicationServiceInterface $invoiceMutationService,
        private readonly InvoicePdfApplicationServiceInterface $invoicePdfApplicationService,
        private readonly CountryOptionsPresenter $countries,
    ) {}

    /**
     * Display paginated list of invoices for authenticated user
     */
    public function index(): View
    {
        $limitsData = $this->resolveLimits();

        return view('frontend.invoices.index', compact('limitsData'));
    }

    /**
     * Show form for creating a new invoice
     */
    public function create(Request $request): View
    {
        $dto = $this->invoiceFormService->prepareCreateData(Auth::user()->id);

        return view('frontend.invoices.create', [
            ...$dto->toArray(),
            'contactId' => $request->integer('contact_id') ?: null,
            'businessId' => $request->integer('business_id') ?: null,
        ]);
    }

    /**
     * Show form for creating a new invoice for guest user
     */
    public function createForGuest(): View
    {
        $data = $this->guestInvoiceApplicationService->prepareGuestCreateData();
        $data['userLoggedIn'] = false;

        return view('frontend.invoices.create', $data);
    }

    /**
     * Show form for editing an invoice
     *
     * @param  int  $id  Invoice ID
     */
    public function edit(string $locale, int $id): View|RedirectResponse
    {
        try {
            $dto = $this->invoiceFormService->prepareEditData(
                Auth::user()->id,
                $id,
                $locale,
            );

            return view('frontend.invoices.edit', $dto->toArray());
        } catch (ModelNotFoundException $e) {
            return redirect()->route('frontend.invoices', ['locale' => $locale])
                ->with('error', __('invoices.messages.edit_error_unauthorized'));
        }
    }

    /**
     * Store a new invoice
     */
    public function store(InvoiceRequest $request): RedirectResponse
    {
        $payload = $this->invoiceRequestAssembler->assemble($request);
        // Let ValidationException bubble up to trigger redirect with errors
        $result = $this->invoiceMutationService->create(
            Auth::user()->id,
            $payload->validated,
            $request->partySelection(),
            $payload->products,
        );
        if ($result->status === InvoiceActionStatus::FAILURE) {
            return back()->with('error', __($result->message ?? 'invoices.messages.create_failed'));
        }
        $locale = app()->getLocale();
        $targetUrl = '/' . $locale . '/invoice';

        return redirect($targetUrl)
            ->with('success', __($result->message ?? 'invoices.messages.created'))
            ->withInput()
            ->withErrors($result->errors ?? []);
    }

    /**
     * Store invoice for guest user using cache
     */
    public function storeGuest(InvoiceRequest $request): JsonResponse|RedirectResponse
    {
        try {
            $payload = $this->invoiceRequestAssembler->assemble($request);
            $response = $this->guestInvoiceApplicationService->storeGuestInvoice(
                $payload->validated,
                $request->oneOffParty('supplier'),
                $request->oneOffParty('customer'),
                $request->oneOffAccount(),
                $request->get('lang'),
            );

            return response()->json($response);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('invoices.messages.validation_failed'),
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Guest invoice creation failed', ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => __('invoices.messages.create_error'),
            ], 500);
        }
    }

    /**
     * Display invoice details
     *
     * @param  int  $id  Invoice ID
     */
    public function show(string $locale, int $id): View|RedirectResponse|JsonResponse
    {
        try {
            $dto = $this->invoiceFormService->prepareShowData(Auth::user()->id, $id);

            return view('frontend.invoices.show', [
                ...$dto->toArray(),
                'countries' => $this->countries->options($locale),
            ]);
        } catch (ModelNotFoundException $e) {
            Log::warning('Invoice not found or unauthorized for show: ID=' . $id);
        } catch (\Exception $e) {
            Log::error('Error viewing invoice: ' . $e->getMessage());
        }

        return redirect()->route('frontend.invoices', ['locale' => $locale])
            ->with('error', __('invoices.messages.show_error'));
    }

    /**
     * Generate and download invoice PDF
     *
     * @param  int  $id  Invoice ID
     */
    public function download(
        string $locale,
        int $id,
        Request $request,
    ): Response|RedirectResponse {
        $result = $this->invoicePdfApplicationService->generate(
            Auth::user()->id,
            $id,
            null,
            $request->get('lang'),
            $request->has('preview'),
        );
        if ($result->status === InvoiceActionStatus::FAILURE) {
            return redirect()->back()->with('error', __($result->message ?? 'invoices.messages.pdf_error'));
        }

        return $result->response;
    }

    /**
     * Generate and download invoice PDF using token (for guests)
     */
    public function downloadWithToken(Request $request): Response|RedirectResponse
    {
        $result = $this->invoicePdfApplicationService->generate(
            null,
            null,
            $request->get('token'),
            $request->get('lang'),
            $request->has('preview'),
        );
        if ($result->status === InvoiceActionStatus::FAILURE) {
            abort($result->message === 'invoices.messages.not_found' ? 404 : 500, __($result->message));
        }

        return $result->response;
    }

    /**
     * Update an existing invoice
     *
     * @param  int  $id  Invoice ID
     */
    public function update(
        InvoiceRequest $request,
        string $locale,
        int $id,
    ): RedirectResponse {
        $payload = $this->invoiceRequestAssembler->assemble($request);
        // Let ValidationException bubble up to trigger redirect with errors
        $result = $this->invoiceMutationService->update(
            Auth::user()->id,
            $id,
            $payload->validated,
            $request->partySelection(),
            $payload->products,
        );
        if ($result->status === InvoiceActionStatus::FAILURE) {
            return back()
                ->with('error', __($result->message ?? 'invoices.messages.update_failed'))
                ->withInput()
                ->withErrors($result->errors ?? []);
        }

        return redirect()->route('frontend.invoice.show', ['id' => $result->invoice->id, 'locale' => app()->getLocale()])
            ->with('success', __($result->message ?? 'invoices.messages.updated'));
    }

    public function destroyGuest(string $locale, string $token): RedirectResponse
    {
        try {
            $deleted = $this->guestInvoiceApplicationService->deleteTemporary($token);

            return redirect()->route('home', ['locale' => app()->getLocale()])
                ->with($deleted ? 'success' : 'error', __($deleted ? 'invoices.messages.deleted_guest' : 'invoices.messages.token_invalid'));
        } catch (\Exception $e) {
            Log::error('Error deleting guest invoice');

            return redirect()->route('home', ['locale' => app()->getLocale()])
                ->with('error', __('invoices.messages.delete_error'));
        }
    }

    /**
     * Mark invoice as paid
     *
     * @param  int  $id  Invoice ID
     */
    public function markAsPaid(string $locale, int $id): RedirectResponse
    {
        $user = Auth::user();
        $result = $user ? $this->invoiceFrontendActions->markAsPaid($user->id, $id) : null;
        $ok = $result?->status === InvoiceActionStatus::SUCCESS;

        return back()->with(
            $ok ? 'success' : 'error',
            __($ok ? ($result->message ?? 'invoices.messages.marked_as_paid') : ($result->message ?? 'invoices.messages.update_error')),
        );
    }

    /**
     * Set invoice pdf template
     *
     * @param  int  $id  Invoice ID
     */
    public function setTemplate(
        string $locale,
        int $id,
        Request $request,
        string $template = 'default',
    ): RedirectResponse {
        $requestTemplate = $request->get('template', $template);
        if ($requestTemplate !== $template) {
            $template = $requestTemplate;
        }
        $user = Auth::user();
        $result = $user ? $this->invoiceFrontendActions->setTemplate(
            $user->id,
            $id,
            $template,
        ) : null;
        $ok = $result?->status === InvoiceActionStatus::SUCCESS;

        return redirect()->route('frontend.invoice.edit', ['locale' => $locale, 'id' => $id])
            ->with($ok ? 'success' : 'error', __($ok ? ($result->message ?? 'invoices.messages.template_set') : ($result->message ?? 'invoices.messages.update_error')));
    }

    /**
     * Batch execute allowed lightweight frontend actions (template/status/mark_paid) via orchestrate().
     * Non-fatal failures accumulate; any sub-action failure returns FAILURE with aggregated errors.
     * Just prepared method for future AJAX support (currently only form POST).
     * To use it, you need to call this method via AJAX or standard form POST.
     * Example payload: { "template": "modern", "status": "partially-paid", "mark_paid": true }
     * Example JS:
     *
     *  const batchActions = {
     *      template: 'modern',
     *      mark_paid: true
     *  };
     *
     *  fetch(`/invoice/${invoiceId}/actions`, {
     *      method: 'POST',
     *      body: JSON.stringify(batchActions),
     *      headers: {'Content-Type': 'application/json'}
     *  })
     *  .then(response => response.json())
     *  .then(result => {
     *      if (result.success) {
     *          showMessage(result.message);
     *          // Možno refresh časti stránky
     *      }
     *  });
     */
    public function batchActions(
        string $locale,
        int $id,
        Request $request,
    ): RedirectResponse {
        $user = Auth::user();
        if (! $user) {
            return back()->with('error', __('users.auth.unauthenticated'));
        }
        // Whitelist and basic validation
        $payload = $request->only([
            'template',
            'status',
            'mark_paid',
        ]);
        $rules = [
            'template' => [
                'sometimes',
                'string',
                'in:default,modern,minimal',
            ],
            'status' => [
                'sometimes',
                Rule::enum(InvoiceStatus::class),
            ],
            'mark_paid' => ['sometimes', 'boolean'],
        ];
        $validator = validator($payload, $rules);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        $validated = $validator->validated();
        // Normalize boolean
        if (array_key_exists('mark_paid', $validated)) {
            $validated['mark_paid'] = filter_var($validated['mark_paid'], FILTER_VALIDATE_BOOLEAN);
        }
        $command = OrchestratedInvoiceActions::fromArray($validated, includeInvoice: true);
        $result = $this->invoiceFrontendActions->orchestrate(
            $user->id,
            $id,
            $command,
        );
        $ok = $result->status === InvoiceActionStatus::SUCCESS;

        return redirect()->route('frontend.invoice.edit', ['locale' => $locale, 'id' => $id])
            ->with($ok ? 'success' : 'error', __($result->message ?? ($ok ? 'invoices.messages.updated' : 'invoices.messages.update_error')));
    }

    /**
     * Centralize limit resolution through application service.
     */
    private function resolveLimits(): array
    {
        $user = Auth::user();
        $result = $user ? $this->invoiceFrontendActions->getLimitData($user->id) : null;
        if (! $result || $result->status !== InvoiceActionStatus::SUCCESS) {
            return [
                'limit' => 0,
                'current_usage' => 0,
                'allowed' => false,
            ];
        }

        return $result->data ?? [
            'limit' => 0,
            'current_usage' => 0,
            'allowed' => false,
        ];
    }
}
