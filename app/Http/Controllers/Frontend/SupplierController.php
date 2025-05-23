<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Http\Requests\SupplierRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Traits\SupplierFormFields;
use App\Services\BankService;
use App\Services\LocaleService;
use App\Services\CountryService;
use App\Repositories\SupplierRepository;

class SupplierController extends Controller
{
    use SupplierFormFields;

    /**
     * Bank service instance
     * 
     * @var \App\Services\BankService
     */
    protected $bankService;

    /**
     * Locale service instance
     * 
     * @var \App\Services\LocaleService
     */
    protected $localeService;

    /**
     * Country service instance
     * 
     * @var \App\Services\CountryService
     */
    protected $countryService;

    /**
     * Supplier repository instance
     * 
     * @var \App\Repositories\SupplierRepository
     */
    protected $supplierRepository;

    /**
     * Constructor
     * 
     * @param BankService $bankService
     * @param LocaleService $localeService
     * @param CountryService $countryService
     * @param SupplierRepository $supplierRepository
     */
    public function __construct(
        BankService $bankService,
        LocaleService $localeService,
        CountryService $countryService,
        SupplierRepository $supplierRepository
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
            
            // Set locale for response
            $locale = $this->localeService->determineLocale($request->get('lang'));
            
            return redirect()->route('frontend.suppliers', ['lang' => $locale])
                            ->with('success', __('suppliers.messages.created'));
        } catch (\Exception $e) {
            Log::error('Error creating supplier: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()->withInput()
                        ->with('error', __('suppliers.messages.error_create'));
        }
    }

    /**
     * Display supplier details and related invoices
     *
     * @param int $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show($id)
    {
        try {
            // Ignore requests for static files
            if (preg_match('/\.(js\.map|css\.map|js|css|png|jpg|gif|svg|woff|woff2|ttf|eot)$/', $id)) {
                return response()->json(['error' => 'Not found'], 404);
            }

            // Check if ID is numeric
            if (!is_numeric($id)) {
                Log::warning('Wrong supplier ID: ' . $id);
                return redirect()
                    ->route('frontend.suppliers', ['lang' => app()->getLocale()])
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
            Log::error('Error showing supplier #' . $id . ': ' . $e->getMessage());
            return redirect()
                ->route('frontend.suppliers', ['lang' => app()->getLocale()])
                ->with('error', __('suppliers.messages.error_show'));
        } catch (\Exception $e) {
            Log::error('Error viewing supplier: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()
                ->route('frontend.suppliers', ['lang' => app()->getLocale()])
                ->with('error', __('suppliers.messages.error_show'));
        }
    }

    /**
     * Show form for editing a supplier
     *
     * @param int $id
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit($id)
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
            Log::error('Error editing supplier #' . $id . ': ' . $e->getMessage());
            return redirect()->route('frontend.suppliers', ['lang' => app()->getLocale()])
                             ->with('error', __('suppliers.messages.error_edit'));
        } catch (\Exception $e) {
            Log::error('Error editing supplier #' . $id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('frontend.suppliers', ['lang' => app()->getLocale()])
                             ->with('error', __('suppliers.messages.error_edit'));
        }
    }

    /**
     * Update supplier data
     *
     * @param SupplierRequest $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(SupplierRequest $request, $id)
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
            
            // Set locale for response
            $locale = $this->localeService->determineLocale($request->get('lang'));
            
            return redirect()
                ->route('frontend.suppliers', ['lang' => $locale])
                ->with('success', __('suppliers.messages.updated'));
        } catch (ModelNotFoundException $e) {
            Log::error('Error updating supplier #' . $id . ': ' . $e->getMessage());
            return redirect()
                ->route('frontend.suppliers', ['lang' => app()->getLocale()])
                ->with('error', __('suppliers.messages.error_update'));
        } catch (\Exception $e) {
            Log::error('Error updating supplier #' . $id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()
                ->route('frontend.suppliers', ['lang' => app()->getLocale()])
                ->with('error', __('suppliers.messages.error_update'));
        }
    }

    /**
     * Delete supplier if it has no associated invoices
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy($id)
    {
        try {
            $supplier = Supplier::where('user_id', Auth::id())->findOrFail($id);
            
            // Check if supplier has associated invoices
            if ($supplier->invoices->count() > 0) {
                return redirect()
                    ->route('frontend.suppliers', ['lang' => app()->getLocale()])
                    ->with('error', __('suppliers.messages.error_delete_invoices'));
            }
            
            $supplier->delete();
            
            return redirect()
                ->route('frontend.suppliers', ['lang' => app()->getLocale()])
                ->with('success', __('suppliers.messages.deleted'));
        } catch (ModelNotFoundException $e) {
            Log::error('Error deleting supplier #' . $id . ': ' . $e->getMessage());
            return redirect()
                ->route('frontend.suppliers', ['lang' => app()->getLocale()])
                ->with('error', __('suppliers.messages.error_delete'));
        } catch (\Exception $e) {
            Log::error('Error deleting supplier #' . $id . ': ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()
                ->route('frontend.suppliers', ['lang' => app()->getLocale()])
                ->with('error', __('suppliers.messages.error_delete'));
        }
    }

    /**
     * Set supplier as default
     * 
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function setDefault($id)
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
                ->route('frontend.suppliers', ['lang' => app()->getLocale()])
                ->with('success', __('suppliers.messages.set_default'));
        } catch (ModelNotFoundException $e) {
            Log::error('Supplier not found for setting default #' . $id . ': ' . $e->getMessage());
            
            return redirect()
                ->route('frontend.suppliers', ['lang' => app()->getLocale()])
                ->with('error', __('suppliers.messages.error_set_default'));
        } catch (\Exception $e) {
            Log::error('Error setting supplier as default #' . $id . ': ' . $e->getMessage());
            
            return redirect()
                ->route('frontend.suppliers', ['lang' => app()->getLocale()])
                ->with('error', __('suppliers.messages.error_set_default'));
        }
    }
}
