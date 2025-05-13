<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceRequest;
use App\Models\User;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Status;
use App\Models\Supplier;
use App\Models\PaymentMethod;
use App\Models\Tax;
use App\Models\Bank;
use App\Traits\InvoiceFormFields;
use App\Services\QrPaymentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class InvoiceController extends Controller
{
    use InvoiceFormFields;

    protected $qrPaymentService;
    
    public function __construct(QrPaymentService $qrPaymentService)
    {
        $this->qrPaymentService = $qrPaymentService;
    }

    /**
     * Display paginated list of invoices for authenticated user
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('frontend.invoices.index');
    }

    /**
     * Show form for creating a new invoice
     * 
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function create(Request $request)
    {
        // Get clients for dropdown
        $clients = Client::where('user_id', Auth::id())->pluck('name', 'id')->toArray();
        
        // Get suppliers for dropdown
        $suppliers = Supplier::where('user_id', Auth::id())->pluck('name', 'id')->toArray();
        
        // Get payment methods for dropdown
        $paymentMethods = PaymentMethod::all()->pluck('slug', 'id')->toArray();;

        // Load all active invoice statuses
        $statuses = Status::pluck('name', 'id')->toArray();
        
        // Get last invoice for the user
        $lastInvoice = Invoice::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->first();

        // Get tax rates for dropdown
        $taxRates = Tax::where('slug', 'dph')
            ->pluck('rate', 'id')
            ->toArray();

        // Get banks for dropdown
        $banks = $this->getBanksForDropdown();

        // Get banksData for JD bank-fields.js
        $banksData = $this->getBanksForJs();
        
        // Get suggested invoice number
        $suggestedNumber = $this->getNextInvoiceNumber();

        // Preselected client if client_id is provided in request
        $selectedClient = null;
        if ($request->has('client_id')) {
            try {
                $selectedClient = Client::where('user_id', Auth::id())
                    ->findOrFail($request->client_id);
            } catch (ModelNotFoundException $e) {
                Log::error('Error loading client: ' . $e->getMessage(), [
                    'client_id' => $request->client_id,
                    'user_id' => Auth::id()
                ]);
                
                // just show flash message
                session()->flash('error', __('clients.messages.not_found'));
            }
        }
        // If no specific client is requested, use default client
        if (!$selectedClient) {
            $selectedClient = Client::where('user_id', Auth::id())
                ->where('is_default', true)
                ->first();
        }
        
        // Find default supplier
        $defaultSupplier = null;
        if ($request->has('supplier_id')) {
            try {
                $defaultSupplier = Supplier::where('user_id', Auth::id())
                    ->findOrFail($request->supplier_id);
            } catch (ModelNotFoundException $e) {
                Log::error('Error loading supplier: ' . $e->getMessage(), [
                    'supplier_id' => $request->supplier_id,
                    'user_id' => Auth::id()
                ]);
            }
        }
        // If no specific supplier is requested, use default supplier
        if (!$defaultSupplier) {
            $defaultSupplier = Supplier::where('user_id', Auth::id())
                ->where('is_default', true)
                ->first();
        }
        // If no default supplier, use the first one
        if (!$defaultSupplier) {
            $defaultSupplier = Supplier::where('user_id', Auth::id())->first();
        }
        
        // Get authenticated user
        $user = Auth::user();
        $userLoggedIn = Auth::check();
        $userInfo = [];
        
        if ($defaultSupplier) {
            $userInfo = [
                'supplier_id' => $defaultSupplier->id,
                'name' => $defaultSupplier->name,
                'street' => $defaultSupplier->street ?? '',
                'city' => $defaultSupplier->city ?? '',
                'zip' => $defaultSupplier->zip ?? '',
                'country' => $defaultSupplier->country ?? 'CZ',
                'ico' => $defaultSupplier->ico ?? '',
                'dic' => $defaultSupplier->dic ?? '',
                'email' => $defaultSupplier->email ?? '',
                'phone' => $defaultSupplier->phone ?? '',
                'account_number' => $defaultSupplier->account_number ?? '',
                'bank_code' => $defaultSupplier->bank_code ?? '',
                'bank_name' => $defaultSupplier->bank_name ?? '',
                'iban' => $defaultSupplier->iban ?? '',
                'swift' => $defaultSupplier->swift ?? '',
                'client_id' => null,
            ];
        } else {
            $userInfo = [
                'name' => $user->name,
                'street' => '',
                'city' => '',
                'zip' => '',
                'country' => 'CZ',
                'ico' => '',
                'dic' => '',
                'email' => $user->email,
                'phone' => '',
                'account_number' => '',
                'bank_code' => '',
                'bank_name' => '',
                'iban' => '',
                'swift' => '',
                'supplier_id' => null,
                'client_id' => null,
            ];
        }
    
        // Prepare client data if client is selected
        $clientInfo = [];
        if ($selectedClient) {
            $clientInfo = [
                'client_id' => $selectedClient->id,
                'client_name' => $selectedClient->name,
                'client_email' => $selectedClient->email,
                'client_phone' => $selectedClient->phone,
                'client_street' => $selectedClient->street,
                'client_city' => $selectedClient->city,
                'client_zip' => $selectedClient->zip,
                'client_country' => $selectedClient->country,
                'client_ico' => $selectedClient->ico,
                'client_dic' => $selectedClient->dic,
            ];
        }

        // Get item units for dropdown
        $itemUnits = [
            __('invoices.units.hours'),
            __('invoices.units.days'),
            __('invoices.units.pieces'),
            __('invoices.units.kilograms'),
            __('invoices.units.grams'),
            __('invoices.units.liters'),
            __('invoices.units.meters'),
            __('invoices.units.cubic_meters'),
            __('invoices.units.centimeters'),
            __('invoices.units.cubic_centimeters'),
            __('invoices.units.milliliters'),
        ];
        
        // Get fields for invoice form from trait
        $fields = $this->getInvoiceFields($clients, $suppliers, $paymentMethods, $statuses);
        
        return view('frontend.invoices.create', compact(
            'userLoggedIn', 'fields', 'clients', 
            'suppliers', 'userInfo', 'clientInfo', 'paymentMethods', 
            'suggestedNumber', 'statuses', 'defaultSupplier', 'banks',
            'banksData', 'taxRates', 'itemUnits'
        ));
    }

    /**
     * Show form for creating a new invoice for guest user
     * 
     * @return \Illuminate\View\View
     */
    public function createForGuest()
    {
        // Get payment methods for dropdown
        $paymentMethods = PaymentMethod::all()->pluck('slug', 'id')->toArray();
        
        // Get statuses for dropdown
        $statuses = Status::pluck('name', 'id')->toArray();

        // Get tax rates for dropdown
        $taxRates = Tax::where('slug', 'dph')
            ->pluck('rate', 'id')
            ->toArray();

        // Get banks for dropdown
        $banks = $this->getBanksForDropdown();

        // Get banksData for JD bank-fields.js
        $banksData = $this->getBanksForJs();
        
        // Get suggested invoice number
        $suggestedNumber = $this->getNextInvoiceNumber();
        
        // Empty arrays for clients and suppliers since guest users don't have any
        $clients = [];
        $suppliers = [];

        // Guest user flag
        $userLoggedIn = false;
        
        // Get fields for invoice form from trait
        $fields = $this->getInvoiceFields($clients, $suppliers, $paymentMethods, $statuses);
        
        // Default values for guest users
        $userInfo = [
            'name' => '',
            'street' => '',
            'city' => '',
            'zip' => '',
            'country' => 'CZ',
            'ico' => '',
            'dic' => '',
            'email' => '',
            'phone' => '',
        ];

        // Get item units for dropdown
        $itemUnits = [
            __('invoices.units.hours'),
            __('invoices.units.days'),
            __('invoices.units.pieces'),
            __('invoices.units.kilograms'),
            __('invoices.units.grams'),
            __('invoices.units.liters'),
            __('invoices.units.meters'),
            __('invoices.units.cubic_meters'),
            __('invoices.units.centimeters'),
            __('invoices.units.cubic_centimeters'),
            __('invoices.units.milliliters'),
        ];
        
        return view('frontend.invoices.create', compact(
            'userLoggedIn', 'fields', 'userInfo', 
            'paymentMethods', 'clients', 'suppliers', 'statuses', 
            'suggestedNumber', 'banks', 'banksData', 'taxRates', 
            'itemUnits'
        ));
    }

    /**
     * Show form for editing an invoice
     * 
     * @param int $id Invoice ID
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit($id)
    {
        $invoice = Invoice::with('client', 'supplier', 'paymentStatus', 'paymentMethod')->findOrFail($id);
        $user = Auth::user();
        $userLoggedIn = Auth::check();
        
        if ($invoice->user_id != Auth::id()) {
            return redirect()->route('frontend.invoices', ['lang' => app()->getLocale()])->with('error', __('invoices.messages.edit_error_unauthorized'));
        }
        
        if ($invoice->client) {
            $invoice->client_name = $invoice->client_name ?? $invoice->client->name;
            $invoice->client_street = $invoice->client_street ?? $invoice->client->street;
            $invoice->client_city = $invoice->client_city ?? $invoice->client->city;
            $invoice->client_zip = $invoice->client_zip ?? $invoice->client->zip;
            $invoice->client_country = $invoice->client_country ?? $invoice->client->country;
            $invoice->client_ico = $invoice->client_ico ?? $invoice->client->ico;
            $invoice->client_dic = $invoice->client_dic ?? $invoice->client->dic;
        }

        if ($invoice->supplier) {
            $invoice->name = $invoice->name ?? $invoice->supplier->name;
            $invoice->street = $invoice->street ?? $invoice->supplier->street;
            $invoice->city = $invoice->city ?? $invoice->supplier->city;
            $invoice->zip = $invoice->zip ?? $invoice->supplier->zip;
            $invoice->country = $invoice->country ?? $invoice->supplier->country;
            $invoice->ico = $invoice->ico ?? $invoice->supplier->ico;
            $invoice->dic = $invoice->dic ?? $invoice->supplier->dic;
            $invoice->account_number = $invoice->account_number ?? $invoice->supplier->account_number;
            $invoice->bank_code = $invoice->bank_code ?? $invoice->supplier->bank_code;
            $invoice->bank_name = $invoice->bank_name ?? $invoice->supplier->bank_name;
            $invoice->iban = $invoice->iban ?? $invoice->supplier->iban;
            $invoice->swift = $invoice->swift ?? $invoice->supplier->swift;
        } else {
            $defaultSupplier = Supplier::where('user_id', Auth::id())
                ->where('is_default', true)
                ->first();
                
            if (!$defaultSupplier) {
                $defaultSupplier = Supplier::where('user_id', Auth::id())->first();
            }
            
            if ($defaultSupplier) {
                $invoice->name = $invoice->name ?? $defaultSupplier->name ?? '';
                $invoice->email = $invoice->email ?? $defaultSupplier->email ?? '';
                $invoice->street = $invoice->street ?? $defaultSupplier->street ?? '';
                $invoice->city = $invoice->city ?? $defaultSupplier->city ?? '';
                $invoice->zip = $invoice->zip ?? $defaultSupplier->zip ?? '';
                $invoice->country = $invoice->country ?? $defaultSupplier->country ?? 'CZ';
                $invoice->ico = $invoice->ico ?? $defaultSupplier->ico ?? '';
                $invoice->dic = $invoice->dic ?? $defaultSupplier->dic ?? '';
                $invoice->account_number = $invoice->account_number ?? $defaultSupplier->account_number ?? '';
                $invoice->bank_code = $invoice->bank_code ?? $defaultSupplier->bank_code ?? '';
                $invoice->bank_name = $invoice->bank_name ?? $defaultSupplier->bank_name ?? '';
                $invoice->iban = $invoice->iban ?? $defaultSupplier->iban ?? '';
                $invoice->swift = $invoice->swift ?? $defaultSupplier->swift ?? '';
            } else {
                $invoice->name = $invoice->name ?? $user->name ?? '';
                $invoice->email = $invoice->email ?? $user->email ?? '';
                $invoice->street = $invoice->street ?? '';
                $invoice->city = $invoice->city ?? '';
                $invoice->zip = $invoice->zip ?? '';
                $invoice->country = $invoice->country ?? 'CZ';
                $invoice->ico = $invoice->ico ?? '';
                $invoice->dic = $invoice->dic ?? '';
                $invoice->account_number = $invoice->account_number ?? '';
                $invoice->bank_code = $invoice->bank_code ?? '';
                $invoice->bank_name = $invoice->bank_name ?? '';
                $invoice->iban = $invoice->iban ?? '';
                $invoice->swift = $invoice->swift ?? '';
            }
        }

        // Get clients for dropdown
        $clients = Client::where('user_id', Auth::id())->pluck('name', 'id')->toArray();
        
        // Get suppliers for dropdown
        $suppliers = Supplier::where('user_id', Auth::id())->pluck('name', 'id')->toArray();

        // Get payment methods for dropdown
        $paymentMethods = PaymentMethod::all()->pluck('slug', 'id')->toArray();
        
        // Load all active invoice statuses
        $statuses = Status::pluck('name', 'id')->toArray();

        // Get tax rates for dropdown
        $taxRates = Tax::where('slug', 'dph')
            ->pluck('rate', 'id')
            ->toArray();

        // Get banks for dropdown
        $banks = $this->getBanksForDropdown();

        // Get banksData for JD bank-fields.js
        $banksData = $this->getBanksForJs();

        // Načtení sazeb DPH pro položky faktury
        $itemUnits = [
            __('invoices.units.hours'),
            __('invoices.units.days'),
            __('invoices.units.pieces'),
            __('invoices.units.kilograms'),
            __('invoices.units.grams'),
            __('invoices.units.liters'),
            __('invoices.units.meters'),
            __('invoices.units.cubic_meters'),
            __('invoices.units.centimeters'),
            __('invoices.units.cubic_centimeters'),
            __('invoices.units.milliliters'),
        ];
        
        // Get fields for invoice form from trait
        $fields = $this->getInvoiceFields($clients, $suppliers, $paymentMethods, $statuses);
        
        return view('frontend.invoices.edit', compact(
            'invoice', 'userLoggedIn', 'fields', 
            'paymentMethods', 'clients', 'suppliers', 'statuses', 
            'user', 'banks', 'banksData', 'taxRates', 'itemUnits'
        ));
    }
    
    /**
     * Store a new invoice
     * 
     * @param InvoiceRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(InvoiceRequest $request)
    {
        try {
            // Get validated data
            $data = $request->validated();
            
            // Add user_id to data
            $data['user_id'] = Auth::id();
        
            if (empty($data['client_id']) && !empty($data['client_name']) && strlen($data['client_name']) >= 3) {
                // Create new client
                $client = Client::create([
                    'name' => $data['client_name'],
                    'street' => $data['client_street'] ?? null,
                    'city' => $data['client_city'] ?? null,
                    'zip' => $data['client_zip'] ?? null,
                    'country' => $data['client_country'] ?? null,
                    'ico' => $data['client_ico'] ?? null,
                    'dic' => $data['client_dic'] ?? null,
                    'user_id' => Auth::id()
                ]);
                
                // Add new client ID to invoice data
                $data['client_id'] = $client->id;
            }
        
            if (empty($data['supplier_id']) && !empty($data['name']) && strlen($data['name']) >= 3) {
                // Create new supplier
                $supplier = Supplier::create([
                    'name' => $data['name'],
                    'street' => $data['street'] ?? null,
                    'city' => $data['city'] ?? null,
                    'zip' => $data['zip'] ?? null,
                    'country' => $data['country'] ?? null,
                    'ico' => $data['ico'] ?? null,
                    'dic' => $data['dic'] ?? null,
                    'user_id' => Auth::id()
                ]);
                
                // Add new supplier ID to invoice data
                $data['supplier_id'] = $supplier->id;
            }

            // Create invoice
            $invoice = Invoice::create($data);
            
            return redirect()->route('frontend.invoices', ['lang' => app()->getLocale()])
                ->with('success', __('invoices.messages.created'));
        } catch (\Exception $e) {
            Log::error(__('invoices.messages.create_error') . $e->getMessage());
            
            return back()->withInput()
                ->with('error', __('invoices.messages.create_error') . $e->getMessage());
        }
    }

    /**
     * Store invoice for guest user using cache
     * 
     * @param InvoiceRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function storeGuest(InvoiceRequest $request)
    {
        try {
            // Get validated data
            $data = $request->validated();
        
            // Set locale based on request or default and add locale to data
            $locale = $request->get('lang', app()->getLocale());
            if (!in_array($locale, config('app.available_locales', ['cs', 'en', 'de', 'sk']))) {
                $locale = config('app.fallback_locale', 'cs');
            }
            $data['lang'] = $locale;
        
            // Convert due_in to integer
            if (isset($data['due_in'])) {
                $data['due_in'] = (int)$data['due_in'];
            }
            
            // Create token to identify the invoice
            $token = Str::random(64);
            
            // Save invoice data to cache with expiration in 10 minutes
            Cache::put('invoice_data_' . $token, $data, now()->addMinutes(10));

            // Sace token to session for later use
            Session::put('last_guest_invoice_token', $token);
            Session::put('last_guest_invoice_number', $data['invoice_vs']);
            Session::put('last_guest_invoice_expires', now()->addMinutes(10)->timestamp);
            
            // Create temporary invoice object
            $tempInvoice = new \stdClass();
            foreach ($data as $key => $value) {
                $tempInvoice->$key = $value;
            }

            // Convert temporary invoice due_in to integer
            if (isset($tempInvoice->due_in)) {
                $tempInvoice->due_in = (int)$tempInvoice->due_in;
            }
            
            // Generate QR code if bank details are available
            $qrCodeBase64 = null;
            try {
                // Check if bank details are available
                if (!empty($tempInvoice->account_number) && !empty($tempInvoice->bank_code)) {
                    $qrCodeBase64 = $this->qrPaymentService->generateQrCodeBase64($tempInvoice);
                } elseif (!empty($tempInvoice->iban)) {
                    $qrCodeBase64 = $this->qrPaymentService->generateQrCodeBase64($tempInvoice);
                } else {
                    Log::warning('For guest user, no bank details available for QR code generation.');
                }
            } catch (\Exception $e) {
                Log::error('Error while generating QR code: ' . $e->getMessage());
            }
            
            // Render the invoice PDF for modal window
            return response()->json([
                'success' => true,
                'message' => __('invoices.messages.created_guest'),
                'invoice_number' => $data['invoice_vs'],
                'download_url' => route('frontend.invoice.download.token', 
                [
                    'token' => $token, 
                    'lang' => $locale
                ]),
                'token' => $token,
                'qr_code' => $qrCodeBase64,
                'has_qr_code' => !empty($qrCodeBase64)
            ]);
        } catch (\Exception $e) {
            Log::error(__('invoices.messages.create_error') . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => __('invoices.messages.create_error'),
                'error' => $e->getMessage()
            ], 422);
        }
    }
    
    /**
     * Display invoice details
     * 
     * @param int $id Invoice ID
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show($id)
    {
        try {
            // Ignore requests for static files
            if (preg_match('/\.(js\.map|css\.map|js|css|png|jpg|gif|svg|woff|woff2|ttf|eot)$/', $id)) {
                // Tiché ignorování požadavků na statické soubory
                return response()->json(['error' => 'Not found'], 404);
            }

            // Check if ID is numeric
            if (!is_numeric($id)) {
                Log::warning('Wrong invoice ID: ' . $id);
                return redirect()
                    ->route('frontend.invoices', ['lang' => app()->getLocale()])
                    ->with('error', __('invoices.messages.invalid_id'));
            }
            // Get invoice with related models
            $invoice = Invoice::with(['supplier',  'client', 'paymentMethod', 'paymentStatus'])
                ->where('user_id', Auth::id())
                ->findOrFail($id);

            if ($invoice->supplier) {
                $invoice->name = $invoice->name ?? $invoice->supplier->name ?? '';
                $invoice->street = $invoice->street ?? $invoice->supplier->street ?? '';
                $invoice->city = $invoice->city ?? $invoice->supplier->city ?? '';
                $invoice->zip = $invoice->zip ?? $invoice->supplier->zip ?? '';
                $invoice->country = $invoice->country ?? $invoice->supplier->country ?? '';
                $invoice->ico = $invoice->ico ?? $invoice->supplier->ico ?? '';
                $invoice->dic = $invoice->dic ?? $invoice->supplier->dic ?? '';
                $invoice->email = $invoice->email ?? $invoice->supplier->email ?? '';
                $invoice->phone = $invoice->phone ?? $invoice->supplier->phone ?? '';
                $invoice->description = $invoice->description ?? $invoice->supplier->description ?? '';
                $invoice->supplier_is_default = $invoice->supplier_is_default ?? $invoice->supplier->is_default ?? '';
                $invoice->account_number = $invoice->account_number ?? $invoice->supplier->account_number ?? '';
                $invoice->bank_code = $invoice->bank_code ?? $invoice->supplier->bank_code ?? '';
                $invoice->bank_name = $invoice->bank_name ?? $invoice->supplier->bank_name ?? '';
                $invoice->iban = $invoice->iban ?? $invoice->supplier->iban ?? '';
                $invoice->swift = $invoice->swift ?? $invoice->supplier->swift ?? '';
            }
            
            return view('frontend.invoices.show', compact('invoice'));
        } catch (ModelNotFoundException $e) {
            Log::warning('Trying to view nonexistent invoice with ID: ' . $id);
            
            return redirect()
                ->route('frontend.invoices', ['lang' => app()->getLocale()])
                ->with('error', __('invoices.messages.error_show'));
        }
    }

    /**
     * Generate and download invoice PDF
     * 
     * @param int $id Invoice ID
     * @param Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function download($id, Request $request)
    {
        try {
            // Get invoice with related models
            $invoice = Invoice::with(['supplier', 'client', 'paymentMethod', 'paymentStatus'])
                ->where('user_id', Auth::id())
                ->findOrFail($id);

            // Set locale based on request or default
            $requestLocale = request()->get('lang');
            $userLocale = Session::get('locale');
            
            $locale = null;
            // First check the request locale
            if ($requestLocale && in_array($requestLocale, config('app.available_locales', ['cs', 'en', 'de', 'sk']))) {
                $locale = $requestLocale;
            } 
            // Next check the session locale
            elseif ($userLocale && in_array($userLocale, config('app.available_locales', ['cs', 'en', 'de', 'sk']))) {
                $locale = $userLocale;
            }
            // Finally check the invoice locale
            else {
                $locale = config('app.locale', 'cs');
            }
        
            $this->setLocaleForPdfGeneration($locale);

            // Data for QR code
            $qrData = [
                'invoice_vs' => $invoice->invoice_vs,
                'payment_amount' => (float)$invoice->payment_amount,
                'payment_currency' => $invoice->payment_currency,
            ];

            // Set bank account details for QR code
            $supplier = $invoice->supplier;
            if ($supplier) {
                if (!empty($supplier->account_number) && !empty($supplier->bank_code)) {
                    $qrData['account_number'] = $supplier->account_number;
                    $qrData['bank_code'] = $supplier->bank_code;
                }
                if (!empty($supplier->iban)) {
                    $qrData['iban'] = $supplier->iban;
                }
            }
            
            // If no bank details in supplier, check invoice
            if (empty($qrData['account_number']) && !empty($invoice->account_number) && !empty($invoice->bank_code)) {
                $qrData['account_number'] = $invoice->account_number;
                $qrData['bank_code'] = $invoice->bank_code;
            }
            
            if (empty($qrData['iban']) && !empty($invoice->iban)) {
                $qrData['iban'] = $invoice->iban;
            }
            
            // Create a clone of the invoice object to avoid modifying the original
            $qrInvoice = clone $invoice;
            
            // Add QR code data to the invoice object
            foreach ($qrData as $key => $value) {
                $qrInvoice->$key = $value;
            }
            
            // Generate QR code with updated data
            $qrCodeBase64 = null;
            try {
                $qrCodeBase64 = $this->qrPaymentService->generateQrCodeBase64($qrInvoice);
            } catch (\Exception $e) {
                Log::error('Error while generating QR code: ' . $e->getMessage());
            }
            
            // Set data for PDF generation
            $data = [
                'invoice' => $invoice,
                'user' => Auth::user(),
                'client' => $invoice->client,
                'supplier' => $invoice->supplier,
                'paymentMethod' => $invoice->paymentMethod,
                'qrCode' => $qrCodeBase64,
                'hasQrCode' => !empty($qrCodeBase64),
                'locale' => $locale // Add locale information
            ];

            // Generating PDF
            $data['locale'] = $locale;
            $pdf = Pdf::loadView('pdfs.invoice', $data);
            
            // Preview or download
            if ($request->has('preview')) {
                app()->setLocale($locale);
                Session::put('locale', $locale);

                $response = $pdf->stream('faktura-'.$invoice->invoice_vs.'.pdf');
                return $response->cookie('locale', $locale, 60 * 24 * 30); // 30 dní
            }
        
            // Add cookie for locale
            $response = $pdf->download('faktura-'.$invoice->invoice_vs.'.pdf');
            return $response->cookie('locale', $locale, 60 * 24 * 30); // 30 days
        } catch (\Exception $e) {
            Log::error('Error while generating PDF invoice: ' . $e->getMessage());
            return redirect()->back()->with('error', __('invoices.messages.pdf_error'));
        }
    }

    /**
     * Generate and download invoice PDF using token (for guests)
     * 
     * @param string $token Invoice token
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function downloadWithToken($token)
    {
        try {
            // Get invoice data from cache
            $invoiceData = Cache::get('invoice_data_' . $token);
            
            if (!$invoiceData) {
                Log::warning('Trying to downlad invoice with token: ' . $token);
                abort(404, __('invoices.messages.token_invalid'));
            }
    
            // set locale based on request or default
            $requestLocale = request()->get('lang');
            $dataLocale = $invoiceData['lang'] ?? null;

            $locale = null;
            if ($requestLocale && in_array($requestLocale, config('app.available_locales', ['cs', 'en', 'de', 'sk']))) {
                $locale = $requestLocale;
            } elseif ($dataLocale && in_array($dataLocale, config('app.available_locales', ['cs', 'en', 'de', 'sk']))) {
                $locale = $dataLocale;
            } else {
                $locale = config('app.fallback_locale', 'cs');
            }

            $this->setLocaleForPdfGeneration($locale);
    
            // Create a new temporary invoice object
            $invoice = new \stdClass();
            foreach ($invoiceData as $key => $value) {
                $invoice->$key = $value;
            }

            // Convert due_in to integer
            if (isset($invoice->due_in)) {
                $invoice->due_in = (int)$invoice->due_in;
            }
    
            // Ensure required properties are set
            $this->ensureProperties($invoice, [
                'payment_method_id', 'due_in', 'payment_status_id', 'payment_currency'
            ]);
    
            // Create client and supplier objects
            $client = new \stdClass();
            $client->name = $invoiceData['client_name'] ?? '';
            $client->street = $invoiceData['client_street'] ?? '';
            $client->city = $invoiceData['client_city'] ?? '';
            $client->zip = $invoiceData['client_zip'] ?? '';
            $client->country = $invoiceData['client_country'] ?? '';
            $client->ico = $invoiceData['client_ico'] ?? '';
            $client->dic = $invoiceData['client_dic'] ?? '';
            $client->email = $invoiceData['client_email'] ?? '';
            $client->phone = $invoiceData['client_phone'] ?? '';
    
            $supplier = new \stdClass();
            $supplier->name = $invoiceData['name'] ?? '';
            $supplier->street = $invoiceData['street'] ?? '';
            $supplier->city = $invoiceData['city'] ?? '';
            $supplier->zip = $invoiceData['zip'] ?? '';
            $supplier->country = $invoiceData['country'] ?? '';
            $supplier->ico = $invoiceData['ico'] ?? '';
            $supplier->dic = $invoiceData['dic'] ?? '';
            $supplier->email = $invoiceData['email'] ?? '';
            $supplier->phone = $invoiceData['phone'] ?? '';
            $supplier->account_number = $invoiceData['account_number'] ?? '';
            $supplier->bank_code = $invoiceData['bank_code'] ?? '';
            $supplier->bank_name = $invoiceData['bank_name'] ?? '';
            $supplier->iban = $invoiceData['iban'] ?? '';
            $supplier->swift = $invoiceData['swift'] ?? '';
    
            // Add supplier to invoice
            $invoice->supplier = $supplier;
    
            // Create a clone invoice object
            $qrInvoice = clone $invoice;
            
            // Set payment information data for QR code
            $qrData = [
                'invoice_vs' => $invoiceData['invoice_vs'],
                'payment_amount' => (float)$invoiceData['payment_amount'],
                'payment_currency' => $invoiceData['payment_currency'],
            ];
            
            // Set bank account details for QR code
            if (!empty($invoiceData['account_number']) && !empty($invoiceData['bank_code'])) {
                $qrData['account_number'] = $invoiceData['account_number'];
                $qrData['bank_code'] = $invoiceData['bank_code'];
            }
            
            if (!empty($invoiceData['iban'])) {
                $qrData['iban'] = $invoiceData['iban'];
            }
            
            // Add QR code data to the invoice object
            foreach ($qrData as $key => $value) {
                $qrInvoice->$key = $value;
            }
    
            // Get payment method
            // Check if payment method ID is set in the invoice data
            $paymentMethod = null;
            if (!empty($invoiceData['payment_method_id'])) {
                $paymentMethod = PaymentMethod::find($invoiceData['payment_method_id']);
            }
            
            // Generate QR code
            $qrCodeBase64 = null;
            try {
                $qrCodeBase64 = $this->qrPaymentService->generateQrCodeBase64($qrInvoice);
            } catch (\Exception $e) {
                Log::error('Error while generation QR code: ' . $e->getMessage());
            }
            
            // Data for PDF generation
            $data = [
                'invoice' => $invoice,
                'user' => null,
                'client' => $client,
                'supplier' => $supplier,
                'paymentMethod' => $paymentMethod,
                'qrCode' => $qrCodeBase64,
                'hasQrCode' => !empty($qrCodeBase64),
                'locale' => $locale // Add locale
            ];
    
            // Generating PDF
            $pdf = Pdf::loadView('pdfs.invoice', $data);

            $response = $pdf->download('faktura-' . $invoice->invoice_vs . '.pdf');

            // Set locale cookie
            return $response->cookie('locale', $locale, 60 * 24 * 30);
        } catch (\Exception $e) {
            Log::error('Error while generating invoice PDF: ' . $e->getMessage());
            abort(500, __('invoices.messages.pdf_error'));
        }
    }

    /**
     * Set application locale for PDF generation
     * 
     * @param string $locale Language code
     * @return void
     */
    private function setLocaleForPdfGeneration(string $locale): void
    {
        if (in_array($locale, config('app.available_locales', ['cs', 'en', 'de', 'sk']))) {
            app()->setLocale($locale);
            Session::put('locale', $locale);
        } else {
            $fallbackLocale = config('app.fallback_locale', 'cs');
            app()->setLocale($fallbackLocale);
            Session::put('locale', $fallbackLocale);
        }
    }

    /**
     * Ensure object has all required properties (with empty values)
     *
     * @param \stdClass $object
     * @param array $properties
     * @return void
     */
    private function ensureProperties(\stdClass $object, array $properties): void
    {
        foreach ($properties as $property) {
            if (!property_exists($object, $property)) {
                if ($property === 'due_in') {
                    $object->$property = 14;
                } elseif ($property === 'payment_method_id' || $property === 'payment_status_id') {
                    $object->$property = 1;
                } elseif ($property === 'payment_amount') {
                    $object->$property = 0;
                } else {
                    $object->$property = '';
                }
            } else if ($property === 'due_in' || $property === 'payment_method_id' || $property === 'payment_status_id') {
                $object->$property = (int)$object->$property;
            } else if ($property === 'payment_amount') {
                $object->$property = (float)$object->$property;
            }
        }
    }

    /**
     * Update an existing invoice
     * 
     * @param InvoiceRequest $request
     * @param int $id Invoice ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(InvoiceRequest $request, $id)
    {
        try {
            $invoice = Invoice::where('user_id', Auth::id())->findOrFail($id);
            if ($invoice->user_id != Auth::id()) {
                return redirect()->route('frontend.invoices', ['lang' => app()->getLocale()])->with('error', __('invoices.messages.update_error_unauthorized'));
            }
            
            // Get validated data
            $data = $request->validated();
        
            if (empty($data['client_id']) && !empty($data['client_name']) && strlen($data['client_name']) >= 3) {
                // Create new client
                $client = new Client();
                $client->user_id = Auth::id();
                $client->name = $data['client_name'];
                $client->street = $data['client_street'] ?? null;
                $client->city = $data['client_city'] ?? null;
                $client->zip = $data['client_zip'] ?? null;
                $client->country = $data['client_country'] ?? 'CZ';
                $client->shortcut = $data['client_shortcut'] ?? null;
                $client->ico = $data['client_ico'] ?? null;
                $client->dic = $data['client_dic'] ?? null;
                $client->email = $data['client_email'] ?? null;
                $client->phone = $data['client_phone'] ?? null;
                $client->description = $data['client_description'] ?? null;
                $client->save();
                
                // Set client
                $data['client_id'] = $client->id;
            }
        
            if (empty($data['supplier_id']) && !empty($data['name']) && strlen($data['name']) >= 3) {
                $user = Auth::user();
                // Create new supplier
                $supplier = new Supplier();
                $supplier->user_id = Auth::id();
                $supplier->name = $data['name'];
                $supplier->street = $data['street'] ?? null;
                $supplier->city = $data['city'] ?? null;
                $supplier->zip = $data['zip'] ?? null;
                $supplier->country = $data['country'] ?? 'CZ';
                $supplier->shortcut = $data['shortcut'] ?? null;
                $supplier->ico = $data['ico'] ?? null;
                $supplier->dic = $data['dic'] ?? null;
                $supplier->email = $data['dic'] ?? null;
                $supplier->phone = $data['dic'] ?? null;
                $supplier->description = $data['dic'] ?? null;
                $supplier->save();
                
                // Set supplier
                $data['supplier_id'] = $supplier->id;
            }
            
            // update invoice
            $invoice->update($data);
            
            return redirect()
                ->route('frontend.invoice.show', ['id' => $invoice->id, 'lang' => app()->getLocale()])
                ->with('success', __('invoices.messages.updated'));
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', __('invoices.messages.update_error') . $e->getMessage());
        }
    }
    
    /**
     * Generate next available invoice number
     * 
     * @return string
     */
    protected function getNextInvoiceNumber()
    {
        $lastInvoice = Invoice::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->first();
    
        if ($lastInvoice) {
            // Extract the last number from the invoice_vs field
            // Assuming the format is YYYYXXXX, where YYYY is the year and XXXX is the number
            preg_match('/(\d+)$/', $lastInvoice->invoice_vs, $matches);
            $nextNumber = isset($matches[1]) ? (int)$matches[1] + 1 : 1;
            return sprintf('%04d', $nextNumber);
        }
        
        return date('Y') . '0001';
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
            $banks[$key]['swift'] = $bank['swift']; 
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

    /**
     * Delete temporary invoice for guest user
     * 
     * @param string $token Invoice token
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteGuestInvoice($token)
    {
        try {
            // Verify that token belongs to current user (session)
            $sessionToken = Session::get('last_guest_invoice_token');
            
            if ($sessionToken !== $token) {
                Log::warning('Pokus o smazání faktury s neplatným tokenem: ' . $token);
                return redirect()->route('home')
                    ->with('error', __('invoices.messages.delete_unauthorized'));
            }

            // Delete invoice data from cache
            Cache::forget('invoice_data_' . $token);
            
            // Clear token from session
            Session::forget('last_guest_invoice_token');
            Session::forget('last_guest_invoice_number');
            Session::forget('last_guest_invoice_expires');
            
            return redirect()->route('home')
                ->with('success', __('invoices.messages.invoice_deleted'));
                
        } catch (\Exception $e) {
            Log::error('Error deleting guest invoice', [
                'token' => substr($token, 0, 8) . '...', // Only log part of the token for security
                'error' => $e->getMessage()
            ]);
            
            return redirect()->route('home')
                ->with('error', __('invoices.messages.delete_error'));
        }
    }

    /**
     * Mark invoice as paid
     *
     * @param int $id Invoice ID
     * @return \Illuminate\Http\RedirectResponse
     */
    public function markAsPaid($id)
    {
        try {
            // Get invoice
            $invoice = Invoice::where('user_id', Auth::id())->findOrFail($id);
            
            // Get status ID for "paid"
            $paidStatusId = Status::where('slug', 'paid')->first()->id ?? null;
            
            if (!$paidStatusId) {
                return back()->with('error', __('invoices.messages.status_not_found'));
            }
            
            // Update invoice status
            $invoice->update([
                'payment_status_id' => $paidStatusId
            ]);
            
            return back()->with('success', __('invoices.messages.marked_as_paid'));
        } catch (\Exception $e) {
            Log::error('Error marking invoice as paid: ' . $e->getMessage());
            return back()->with('error', __('invoices.messages.update_error'));
        }
    }
}
