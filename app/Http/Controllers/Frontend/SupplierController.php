<?php

namespace App\Http\Controllers\Frontend;

use App\Application\Party\Contracts\PartyApplicationServiceInterface as InvoicePartyServiceInterface;
// Removed direct Supplier model usage (delegated to party service)
use App\Application\Payment\Contracts\BankApplicationServiceInterface;
use App\Application\Payment\Contracts\BankApplicationServiceInterface as BankServiceInterface;
use App\Application\Shared\Geography\Contracts\CountryApplicationServiceInterface as CountryServiceInterface;
use App\Application\User\Contracts\UELSApplicationServiceInterface as UELSService;
use App\Domain\User\Exceptions\EntityLimitExceededException;
use App\Http\Controllers\Controller;
use App\Http\Requests\SupplierRequest;
use App\Infrastructure\Forms\Party\SupplierFormFields;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SupplierController extends Controller
{
    use SupplierFormFields; // Frontend limits via observers

    /**
     * Bank service instance
     *
     * @var BankApplicationServiceInterface
     */
    protected $bankService;

    /**
     * Country service instance
     *
     * @var CountryServiceInterface
     */
    protected $countryService;

    /**
     * Party service facade instance
     *
     * @var InvoicePartyServiceInterface
     */
    protected $partyService;

    /**
     * Universal limit service instance
     *
     * @var UELSService
     */
    protected $limitService;

    /**
     * Constructor
     */
    public function __construct(
        BankServiceInterface $bankService,
        CountryServiceInterface $countryService,
        InvoicePartyServiceInterface $partyService,
        UELSService $limitService,
    ) {
        $this->bankService = $bankService;
        $this->countryService = $countryService;
        $this->partyService = $partyService;
        $this->limitService = $limitService;
    }

    /**
     * Display paginated list of user suppliers
     *
     * @return View
     */
    public function index()
    {
        // get current logged user's suppliers limits
        $limitsData = $this->getSuppliersLimitStats();

        return view('frontend.suppliers.index', compact('limitsData'));
    }

    /**
     * Show form for creating a new supplier
     *
     * @return View
     */
    public function create()
    {
        // Get fields from trait
        $fields = $this->getSupplierFields();

        // Banks dropdown
        $banks = $this->bankService->getBanksForDropdown();

        // Get banksData for JD bank-fields.js
        $banksData = $this->bankService->getBanksForJs();

        // Get countries for dropdown
        $countries = $this->countryService->getCountryCodesForSelect();

        // Get current user
        $user = Auth::user();

        // get current logged user's suppliers limits
        $limitsData = $this->getSuppliersLimitStats($user);

        $supplierInfo = [
            'name' => $user->name ?? '',
            'street' => '',
            'city' => '',
            'zip' => '',
            'country' => 'CZ',
            'ico' => '',
            'dic' => '',
            'email' => $user->email ?? '',
            'phone' => '',
            'description' => '',
            'is_default' => '',
            'account_number' => '',
            'bank_code' => '',
            'iban' => '',
            'swift' => '',
            'bank_name' => '',
        ];

        return view('frontend.suppliers.create', [
            'fields' => $fields,
            'supplierInfo' => $supplierInfo,
            'banks' => $banks,
            'banksData' => $banksData,
            'countries' => $countries,
            'limitsData' => $limitsData,
        ]);
    }

    /**
     * Store a new supplier
     *
     * @return RedirectResponse
     */
    public function store(SupplierRequest $request)
    {
        $validatedData = $request->validated();

        try {
            // Pass UploadedFile directly; Supplier model mutator + FileUploadService will handle storage & thumbnails
            if ($request->hasFile('supplier_logo')) {
                $validatedData['supplier_logo'] = $request->file('supplier_logo');
            }

            // Get User
            $user = Auth::user();

            // Request-level BaseEntityRequest already enforced limits for create new.
            // We keep a lightweight guard only when creating a brand new supplier (no supplier_id) to provide friendly flash error
            if ($user && empty($validatedData['supplier_id'])) {
                // Check supplier creation permission via UELS (not client)
                $canCreate = $this->limitService->canUserCreateEntity($user->id, 'supplier');
                if (! $canCreate) {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', trans('suppliers.messages.limit_exceeded'));
                }
            }

            // Create or reuse supplier with flag
            $resolved = $this->partyService->resolveOrCreateSupplierWithFlag($user->id, $validatedData);
            $supplier = $resolved['supplier'];
            // Usage recorded by SupplierObserver when created

            // Get locale from route parameters (since we're in localized route group)
            $locale = $request->route('locale') ?? 'cs';

            return redirect()->route('frontend.suppliers', ['locale' => $locale])
                ->with('success', __('suppliers.messages.created'));
        } catch (EntityLimitExceededException $e) {
            return back()->withInput()->with('error', trans('suppliers.messages.limit_exceeded'));
        } catch (ValidationException $e) {
            // Handle file upload validation errors with detailed messages
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', __('suppliers.messages.error_create'));
        } catch (\Exception $e) {
            Log::error('Supplier creation failed', [
                'userId' => Auth::id(),
                'exception' => $e,
            ]);

            return back()->withInput()
                ->with('error', __('suppliers.messages.error_create'));
        }
    }

    /**
     * Display supplier details and related invoices
     *
     * @return View|RedirectResponse
     */
    public function show(string $locale, int $id)
    {
        try {
            // Ignore requests for static files
            if (preg_match('/\.(js\.map|css\.map|js|css|png|jpg|gif|svg|woff|woff2|ttf|eot)$/', (string) $id)) {
                return redirect()
                    ->route('frontend.suppliers', ['locale' => $locale])
                    ->with('error', __('suppliers.messages.error_show'));
            }

            // Check if ID is numeric
            if (! is_numeric($id)) {
                return redirect()
                    ->route('frontend.suppliers', ['locale' => $locale])
                    ->with('error', __('suppliers.messages.invalid_id'));
            }

            // Get User
            $user = Auth::user();

            // Find supplier
            $supplier = $this->partyService->findSupplier($user->id, $id);

            // get current logged user's suppliers limits
            $limitsData = $this->getSuppliersLimitStats($user);

            return view('frontend.suppliers.show', compact('supplier', 'limitsData'));
        } catch (ModelNotFoundException $e) {
            return redirect()
                ->route('frontend.suppliers', ['locale' => $locale])
                ->with('error', __('suppliers.messages.error_show'));
        } catch (\Exception $e) {
            return redirect()
                ->route('frontend.suppliers', ['locale' => $locale])
                ->with('error', __('suppliers.messages.error_show'));
        }
    }

    /**
     * Show form for editing a supplier
     *
     * @return View|RedirectResponse
     */
    public function edit(string $locale, int $id)
    {
        try {
            // Get User
            $user = Auth::user();

            // Find supplier
            $supplier = $this->partyService->findSupplier($user->id, $id);

            // Get fields from trait
            $fields = $this->getSupplierFields();

            // Banks dropdown
            $banks = $this->bankService->getBanksForDropdown();

            // Get banksData for JD bank-fields.js
            $banksData = $this->bankService->getBanksForJs();

            // Get countries for dropdown
            $countries = $this->countryService->getCountryCodesForSelect();

            // get current logged user's suppliers limits
            $limitsData = $this->getSuppliersLimitStats($user);

            return view('frontend.suppliers.edit', [
                'supplier' => $supplier,
                'fields' => $fields,
                'banks' => $banks,
                'banksData' => $banksData,
                'countries' => $countries,
                'limitsData' => $limitsData,
            ]);

        } catch (ModelNotFoundException $e) {
            return redirect()->route('frontend.suppliers', ['locale' => $locale])
                ->with('error', __('suppliers.messages.error_edit'));
        } catch (\Exception $e) {
            return redirect()->route('frontend.suppliers', ['locale' => $locale])
                ->with('error', __('suppliers.messages.error_edit'));
        }
    }

    /**
     * Update supplier data
     *
     * @return RedirectResponse
     */
    public function update(
        SupplierRequest $request,
        string $locale,
        int $id,
    ) {
        try {
            // Get User
            $user = Auth::user();

            // Find supplier
            $supplier = $this->partyService->findSupplier($user->id, $id);

            $validatedData = $request->validated();
            $validatedData['is_default'] = isset($validatedData['is_default']) && $validatedData['is_default'] == 1;

            // Handle file upload if provided: proactively delete old file, then pass UploadedFile to mutator
            if ($request->hasFile('supplier_logo')) {
                if (! empty($supplier->supplier_logo)) {
                    try {
                        Storage::disk('public')->delete($supplier->supplier_logo);
                    } catch (\Throwable) {
                    }
                }
                $validatedData['supplier_logo'] = $request->file('supplier_logo');
            }

            // If setting this supplier as default, unset all others
            if ($validatedData['is_default']) {
                $this->partyService->setSupplierDefault($user->id, $supplier->id);
            }
            $this->partyService->updateSupplier($supplier->id, $validatedData);

            return redirect()
                ->route('frontend.suppliers', ['locale' => $locale])
                ->with('success', __('suppliers.messages.updated'));

        } catch (ModelNotFoundException $e) {
            return redirect()
                ->route('frontend.suppliers', ['locale' => $locale])
                ->with('error', __('suppliers.messages.error_update'));
        } catch (ValidationException $e) {
            // Handle file upload validation errors with detailed messages
            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput()
                ->with('error', __('suppliers.messages.validation_failed'));
        } catch (\Exception $e) {
            Log::error('Supplier update failed', [
                'supplierId' => $id,
                'userId' => Auth::id(),
                'exception' => $e,
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', __('suppliers.messages.error_update'));
        }
    }

    /**
     * Delete supplier if it has no associated invoices
     *
     * @return RedirectResponse
     */
    public function destroy(string $locale, int $id)
    {
        try {
            // Get User
            $user = Auth::user();

            // Find supplier
            $supplier = $this->partyService->findSupplier($user->id, $id);
            if (! $this->partyService->deleteSupplier($user->id, $supplier->id)) {
                return redirect()->route('frontend.suppliers', ['locale' => $locale])
                    ->with('error', __('suppliers.messages.error_delete_invoices'));
            }

            return redirect()
                ->route('frontend.suppliers', ['locale' => $locale])
                ->with('success', __('suppliers.messages.deleted'));
        } catch (ModelNotFoundException $e) {
            return redirect()
                ->route('frontend.suppliers', ['locale' => $locale])
                ->with('error', __('suppliers.messages.error_delete'));
        } catch (\Exception $e) {
            return redirect()
                ->route('frontend.suppliers', ['locale' => $locale])
                ->with('error', __('suppliers.messages.error_delete'));
        }
    }

    /**
     * Get current user's products limits
     *
     * @param  User|null  $user
     */
    public function getSuppliersLimitStats($user = null): array
    {
        if ($user === null) {
            $user = Auth::user();
        }
        if ($user) {
            $bestPeriod = $this->limitService->getBestPeriodType(
                $user->id,
                'supplier',
                'count',
            );
            $stats = $this->limitService->getUsageStatistics(
                $user->id,
                'supplier',
                'count',
                $bestPeriod,
            );
            $limit = $stats['limit'] ?? (($stats['remaining'] ?? null) !== null ? (int) $stats['remaining'] + (int) ($stats['current_usage'] ?? 0) : 0);
            $current = (int) ($stats['current_usage'] ?? 0);
            $canCreate = $stats['can_create'] ?? ($limit > $current);

            return [
                'limit' => $limit,
                'current_usage' => $current,
                'allowed' => $canCreate,
            ];
        } else {
            return [
                'limit' => 0,
                'current_usage' => 0,
                'allowed' => false,
            ];
        }
    }
}
