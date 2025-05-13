<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Http\Requests\SupplierRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Models\Bank;
use App\Traits\SupplierFormFields;

class SupplierController extends Controller
{
    use SupplierFormFields;

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
        $banks = $this->getBanksForDropdown();

        // Get banksData for JD bank-fields.js
        $banksData = $this->getBanksForJs();

        $supplierInfo = [
            'name' => $supplier->name ?? '',
            'street' => $supplier->street ?? '',
            'city' => $supplier->city ?? '',
            'zip' => $supplier->zip ?? '',
            'country' => $supplier->country ?? 'Czech Republic',
            'ico' => $supplier->ico ?? '',
            'dic' => $supplier->dic ?? '',
            'email' => $supplier->email ?? '',
            'phone' => $supplier->phone ?? '',
            'description' => $supplier->description ?? '',
            'is_default' => $supplier->is_default ?? '',
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
            'banksData' => $banksData
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
            // Add authenticated user ID
            $validatedData['user_id'] = Auth::id();
            
            // Set as default if it's the first supplier
            if (Supplier::where('user_id', Auth::id())->count() === 0) {
                $validatedData['is_default'] = true;
            }
            
            $supplier = Supplier::create($validatedData);
            
            return redirect()->route('frontend.suppliers', ['lang' => app()->getLocale()])
                            ->with('success', __('suppliers.messages.created'));
        } catch (\Exception $e) {
            Log::error('Error creating supplier: ' . $e->getMessage());
            
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
                Log::warning('Wrong client ID: ' . $id);
                return redirect()
                    ->route('frontend.clients', ['lang' => app()->getLocale()])
                    ->with('error', __('clients.messages.invalid_id'));
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
                ->with('error', __('suppliers.messages.error_update'));
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
            $banks = $this->getBanksForDropdown();

            // Get banksData for JD bank-fields.js
            $banksData = $this->getBanksForJs();
            
            return view('frontend.suppliers.edit', [
                'supplier' => $supplier,
                'fields' => $fields,
                'banks' => $banks,
                'banksData' => $banksData,
            ]);
            
        } catch (ModelNotFoundException $e) {
            Log::error('Error editing supplier #' . $id . ': ' . $e->getMessage());
            return redirect()->route('frontend.suppliers', ['lang' => app()->getLocale()])
                             ->with('error', __('suppliers.messages.error_update'));
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
            
            $supplier->update($validatedData);
            
            return redirect()
                ->route('frontend.suppliers', ['lang' => app()->getLocale()])
                ->with('success', __('suppliers.messages.updated'));
        } catch (ModelNotFoundException $e) {
            Log::error('Error updating supplier #' . $id . ': ' . $e->getMessage());
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
        }
    }

    /**
     * Get list of banks with codes for dropdown
     * 
     * @return array
     */
    private function getBanksForDropdown(): array
    {
        
        $banks = Bank::where('country', 'CZ')
            ->orderBy('created_at', 'desc')
            ->get()->toArray();

        foreach ($banks as $key => $bank) {
            $banks[$key]['text'] = $bank['name'] . ' (' . $bank['code'] . ')';
            $banks[$key]['value'] = $bank['code'];
        }
        $banks[0] = __('suppliers.fields.select_bank');

        return $banks;
    }

    /**
     * Get list of banks with codes for dropdown
     * 
     * @return array
     */
    private function getBanksForJs(): array
    {
        
        $banks = Bank::where('country', 'CZ')
            ->orderBy('created_at', 'desc')
            ->get()->toArray();

        $banksData = [];
        foreach ($banks as $key => $bank) {
            $banksData[$bank['code']]['text'] = $bank['name'] . ' (' . $bank['code'] . ')';
            $banksData[$bank['code']]['swift'] = $bank['swift']; 
        }

        return $banksData;
    }
}
