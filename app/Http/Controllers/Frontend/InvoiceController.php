<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceRequest;
use App\Application\Invoice\Contracts\InvoiceFormApplicationServiceInterface;
use App\Application\Invoice\Contracts\GuestInvoiceApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceFrontendActionsApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceRequestAssemblerInterface;
use App\Application\Invoice\Contracts\InvoiceMutationApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoicePdfApplicationServiceInterface;
use App\Application\Invoice\DTO\InvoiceActionStatus;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

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
    ) {}

    /**
     * Display paginated list of invoices for authenticated user
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $limitsData = $this->resolveLimits();
        return view('frontend.invoices.index', compact('limitsData'));
    }

    /**
     * Show form for creating a new invoice
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        $dto = $this->invoiceFormService->prepareCreateData(Auth::user()->id, $request);
        return view('frontend.invoices.create', $dto->toArray());
    }

    /**
     * Show form for creating a new invoice for guest user
     *
     * @return \Illuminate\View\View
     */
    public function createForGuest()
    {
        $data = $this->guestInvoiceApplicationService->prepareGuestCreateData();
        $data['userLoggedIn'] = false;
        return view('frontend.invoices.create', $data);
    }

    /**
     * Show form for editing an invoice
     *
     * @param string $locale
     * @param int $id Invoice ID
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(string $locale, int $id)
    {
        try {
            $dto = $this->invoiceFormService->prepareEditData(Auth::user()->id, $id, $locale);
            return view('frontend.invoices.edit', $dto->toArray());
        } catch (ModelNotFoundException $e) {
            return redirect()->route('frontend.invoices', ['locale' => $locale])
                ->with('error', __('invoices.messages.edit_error_unauthorized'));
        }
    }

    /**
     * Store a new invoice
     *
     * @param InvoiceRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(InvoiceRequest $request)
    {
        $payload = $this->invoiceRequestAssembler->assemble(Auth::user(), $request);
        // Let ValidationException bubble up to trigger redirect with errors
        $result = $this->invoiceMutationService->create(Auth::user()->id, $payload->validated, $payload->products);
        if ($result->status === InvoiceActionStatus::FAILURE) {
            return back()->with('error', __($result->message ?? 'invoices.messages.create_failed'));
        }
        $locale = app()->getLocale();
        $targetUrl = '/'.$locale.'/invoice';
        return redirect($targetUrl)
            ->with('success', __($result->message ?? 'invoices.messages.created'))
            ->withInput()
            ->withErrors($result->errors ?? []);
    }

    /**
     * Store invoice for guest user using cache
     *
     * @param InvoiceRequest $request
    * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function storeGuest(InvoiceRequest $request)
    {
        try {
            $payload = $this->invoiceRequestAssembler->assemble(null, $request, true);
            $response = $this->guestInvoiceApplicationService->storeGuestInvoice(
                $payload->validated,
                $payload->products,
                $request->get('lang')
            );
            return response()->json($response);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => __('invoices.messages.validation_failed'),
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error(__('invoices.messages.create_error') . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => __('invoices.messages.create_error') . ': ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display invoice details
     *
     * @param string $locale
     * @param int $id Invoice ID
    * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function show(string $locale, int $id)
    {
        try {
            $dto = $this->invoiceFormService->prepareShowData(Auth::user()->id, $id);
            return view('frontend.invoices.show', $dto->toArray());
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
     * @param string $locale
     * @param int $id Invoice ID
     * @param Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function download(string $locale, int $id, Request $request)
    {
        $result = $this->invoicePdfApplicationService->generate(Auth::user()->id, $id, null, $request->get('lang'), $request->has('preview'));
        if ($result->status === InvoiceActionStatus::FAILURE) {
            return redirect()->back()->with('error', __($result->message ?? 'invoices.messages.pdf_error'));
        }
        return $result->response;
    }

    /**
     * Generate and download invoice PDF using token (for guests)
     *
     * @param Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function downloadWithToken(Request $request)
    {
        $result = $this->invoicePdfApplicationService->generate(null, null, $request->get('token'), $request->get('lang'), $request->has('preview'));
        if ($result->status === InvoiceActionStatus::FAILURE) {
            abort( $result->message === 'invoices.messages.not_found' ? 404 : 500, __($result->message));
        }
        return $result->response;
    }

    /**
     * Update an existing invoice
     *
     * @param InvoiceRequest $request
     * @param string $locale
     * @param int $id Invoice ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(InvoiceRequest $request, string $locale, int $id)
    {
        $payload = $this->invoiceRequestAssembler->assemble(Auth::user(), $request);
        // Let ValidationException bubble up to trigger redirect with errors
        $result = $this->invoiceMutationService->update(Auth::user()->id, $id, $payload->validated, $payload->products);
        if ($result->status === InvoiceActionStatus::FAILURE) {
            return back()
                ->with('error', __($result->message ?? 'invoices.messages.update_failed'))
                ->withInput()
                ->withErrors($result->errors ?? []);
        }
        return redirect()->route('frontend.invoice.show', ['id' => $result->invoice->id, 'locale' => app()->getLocale()])
            ->with('success', __($result->message ?? 'invoices.messages.updated'));
    }

    /**
     * Delete temporary invoice for guest user
     *
     * @param string $locale
     * @param string $token Invoice token
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteGuestInvoice(string $locale, string $token)
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
     * @param string $locale
     * @param int $id Invoice ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAsPaid(string $locale, int $id)
    {
        $user = Auth::user();
        // Directly reuse changeStatus orchestraci (sjednocení s markAsPaid -> changeStatus('paid'))
        $result = $user ? $this->invoiceFrontendActions->changeStatus($user->id, $id, 'paid') : null;
        $ok = $result?->status === InvoiceActionStatus::SUCCESS;
        return back()->with(
            $ok ? 'success' : 'error',
            __($ok ? ($result->message ?? 'invoices.messages.marked_as_paid') : ($result->message ?? 'invoices.messages.update_error'))
        );
    }

    /**
     * Set invoice pdf template
     *
     * @param string $locale
     * @param int $id Invoice ID
     * @param \Illuminate\Http\Request $request
     * @param string $template Template name
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setTemplate(string $locale, int $id, Request $request, string $template = 'default')
    {
        $requestTemplate = $request->get('template', $template);
        if ($requestTemplate !== $template) { $template = $requestTemplate; }
        $user = Auth::user();
        $result = $user ? $this->invoiceFrontendActions->setTemplate($user->id, $id, $template) : null;
        $ok = $result?->status === InvoiceActionStatus::SUCCESS;
        return redirect()->route('frontend.invoice.edit', [ 'locale' => $locale, 'id' => $id ])
            ->with($ok ? 'success' : 'error', __($ok ? ($result->message ?? 'invoices.messages.template_set') : ($result->message ?? 'invoices.messages.update_error')));
    }

    /**
     * Batch execute allowed lightweight frontend actions (template/status/mark_paid) via orchestrate().
     * Non-fatal failures accumulate; any sub-action failure returns FAILURE with aggregated errors.
     * Just prepared method for future AJAX support (currently only form POST).
     * To use it, you need to call this method via AJAX or standard form POST.
     * Example payload: { "template": "modern", "status": "sent", "mark_paid": true }
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
    public function batchActions(string $locale, int $id, Request $request)
    {
        $user = Auth::user();
        if (!$user) { return back()->with('error', __('auth.failed')); }
        // Whitelist and basic validation
        $payload = $request->only(['template','status','mark_paid']);
        $rules = [
            'template' => ['sometimes','string','in:default,modern,minimal'],
            'status' => ['sometimes','string','max:50'],
            'mark_paid' => ['sometimes','boolean'],
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
        $command = \App\Application\Invoice\DTO\OrchestratedInvoiceActions::fromArray($validated, includeInvoice: true);
        $result = $this->invoiceFrontendActions->orchestrate($user->id, $id, $command);
        $ok = $result->status === InvoiceActionStatus::SUCCESS;
        return redirect()->route('frontend.invoice.edit', [ 'locale' => $locale, 'id' => $id ])
            ->with($ok ? 'success' : 'error', __($result->message ?? ($ok ? 'invoices.messages.updated' : 'invoices.messages.update_error')));
    }

    /**
     * Centralize limit resolution through application service.
     */
    private function resolveLimits(): array
    {
        $user = Auth::user();
        $result = $user ? $this->invoiceFrontendActions->getLimitData($user->id) : null;
        if (!$result || $result->status !== InvoiceActionStatus::SUCCESS) {
            return [ 'limit' => 0, 'current_usage' => 0, 'allowed' => false ];
        }
        return $result->data ?? [ 'limit' => 0, 'current_usage' => 0, 'allowed' => false ];
    }
}
