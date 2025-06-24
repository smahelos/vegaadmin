<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Http\Requests\SupplierRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Traits\SupplierFormFields;
use App\Contracts\BankServiceInterface;
use App\Contracts\CountryServiceInterface;
use App\Contracts\LocaleServiceInterface;
use App\Contracts\SupplierRepositoryInterface;

class SupplierController extends Controller
{
    use SupplierFormFields;

    /**
     * Bank service instance
     * 
     * @var \App\Contracts\BankServiceInterface
     */
    protected $bankService;

    /**
     * Locale service instance
     * 
     * @var LocaleServiceInterface
     */
    protected $localeService;

    /**
     * Country service instance
     * 
     * @var CountryServiceInterface
     */
    protected $countryService;

    /**
     * Supplier repository instance
     * 
     * @var SupplierRepositoryInterface
     */
    protected $supplierRepository;

    /**
     * Constructor
     * 
     * @param BankServiceInterface $bankService
     * @param LocaleServiceInterface $localeService
     * @param CountryServiceInterface $countryService
     * @param SupplierRepositoryInterface $supplierRepository
     */
    public function __construct(
        BankServiceInterface $bankService,
        LocaleServiceInterface $localeService,
        CountryServiceInterface $countryService,
        SupplierRepositoryInterface $supplierRepository
    ) {
        $this->bankService = $bankService;
        $this->localeService = $localeService;
        $this->countryService = $countryService;
        $this->supplierRepository = $supplierRepository;
    }

    /**
     * Display paginated list of user suppliers
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('frontend.suppliers.index');
    }

    /**
     * Show form for creating a new supplier
     *
     * @return \Illuminate\View\View
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

        $user = Auth::user();
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
            'countries' => $countries
        ]);
    }

    /**
     * Store a new supplier
     *
     * @param SupplierRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(SupplierRequest $request)
    {
        $validatedData = $request->validated();
        
        try {
            // Create new supplier using repository
            $supplier = $this->supplierRepository->create($validatedData);
            
            // Get locale from route parameters (since we're in localized route group)
            $locale = $request->route('locale') ?? 'cs';
            
            return redirect()->route('frontend.suppliers', ['locale' => $locale])
                            ->with('success', __('suppliers.messages.created'));
        } catch (\Exception $e) {
            return back()->withInput()
                        ->with('error', __('suppliers.messages.error_create'));
        }
    }

    /**
     * Display supplier details and related invoices
     *
     * @param string $locale
     * @param int $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show(string $locale, int $id)
    {
        try {
            // Ignore requests for static files
            if (preg_match('/\.(js\.map|css\.map|js|css|png|jpg|gif|svg|woff|woff2|ttf|eot)$/', $id)) {
                return response()->json(['error' => 'Not found'], 404);
            }

            // Check if ID is numeric
            if (!is_numeric($id)) {
                return redirect()
                    ->route('frontend.suppliers', ['locale' => $locale])
                    ->with('error', __('suppliers.messages.invalid_id'));
            }

            // Get supplier by ID, only for authenticated user
            $supplier = Supplier::where('user_id', Auth::id())->findOrFail($id);
            $invoices = $supplier->invoices()
                ->with(['paymentMethod', 'paymentStatus'])
                ->orderBy('created_at', 'desc')
                ->get();
            
            return view('frontend.suppliers.show', compact('supplier', 'invoices'));
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
     * @param string $locale
     * @param int $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(string $locale, int $id)
    {
        try {
            // Get supplier by ID, only for authenticated user
            $supplier = Supplier::where('user_id', Auth::id())
                            ->findOrFail($id);
                            
            // Get fields from trait
            $fields = $this->getSupplierFields();
            
            // Banks dropdown
            $banks = $this->bankService->getBanksForDropdown();

            // Get banksData for JD bank-fields.js
            $banksData = $this->bankService->getBanksForJs();

            // Get countries for dropdown
            $countries = $this->countryService->getCountryCodesForSelect();

            return view('frontend.suppliers.edit', [
                'supplier' => $supplier,
                'fields' => $fields,
                'banks' => $banks,
                'banksData' => $banksData,
                'countries' => $countries
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
     * @param SupplierRequest $request
     * @param string $locale
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(SupplierRequest $request, string $locale, int $id)
    {
        try {
            $supplier = Supplier::where('user_id', Auth::id())->findOrFail($id);
            
            $validatedData = $request->validated();
            $validatedData['is_default'] = isset($validatedData['is_default']) && $validatedData['is_default'] == 1;
            
            // If setting this supplier as default, unset all others
            if ($validatedData['is_default']) {
                Supplier::where('user_id', Auth::id())
                    ->where('id', '!=', $id)
                    ->update(['is_default' => false]);
            }
            
            $supplier->update($validatedData);
            
            return redirect()
                ->route('frontend.suppliers', ['locale' => $locale])
                ->with('success', __('suppliers.messages.updated'));
        } catch (ModelNotFoundException $e) {
            return redirect()
                ->route('frontend.suppliers', ['locale' => $locale])
                ->with('error', __('suppliers.messages.error_update'));
        } catch (\Exception $e) {
            return redirect()
                ->route('frontend.suppliers', ['locale' => $locale])
                ->with('error', __('suppliers.messages.error_update'));
        }
    }

    /**
     * Delete supplier if it has no associated invoices
     *
     * @param string $locale
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(string $locale, int $id)
    {
        try {
            $supplier = Supplier::where('user_id', Auth::id())->findOrFail($id);
            
            // Check if supplier has associated invoices
            if ($supplier->invoices->count() > 0) {
                return redirect()
                    ->route('frontend.suppliers', ['locale' => $locale])
                    ->with('error', __('suppliers.messages.error_delete_invoices'));
            }
            
            $supplier->delete();
            
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
    }    /**
     * Set supplier as default
     *
     * @param string $locale
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setDefault(string $locale, int $id)
    {
        try {
            // Find supplier
            $supplier = Supplier::where('user_id', Auth::id())->findOrFail($id);
            
            // Remove default flag from all other suppliers
            Supplier::where('user_id', Auth::id())
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
            
            // Set this supplier as default
            $supplier->update(['is_default' => true]);
            
            return redirect()
                ->route('frontend.suppliers', ['locale' => $locale])
                ->with('success', __('suppliers.messages.set_default'));
        } catch (ModelNotFoundException $e) {
            return redirect()
                ->route('frontend.suppliers', ['locale' => $locale])
                ->with('error', __('suppliers.messages.error_set_default'));
        } catch (\Exception $e) {
            return redirect()
                ->route('frontend.suppliers', ['locale' => $locale])
                ->with('error', __('suppliers.messages.error_set_default'));
        }
    }
}
