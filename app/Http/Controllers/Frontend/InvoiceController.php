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
use App\Models\InvoiceProduct;
use App\Traits\InvoiceFormFields;
use App\Services\QrPaymentService;
use App\Services\InvoiceService;
use App\Services\InvoicePdfService;
use App\Services\LocaleService;
use App\Services\BankService;
use App\Repositories\ClientRepository;
use App\Repositories\SupplierRepository;
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
    protected $invoiceService;
    protected $invoicePdfService;
    protected $localeService;
    protected $bankService;
    protected $clientRepository;
    protected $supplierRepository;
    
    public function __construct(
        QrPaymentService $qrPaymentService,
        InvoiceService $invoiceService,
        InvoicePdfService $invoicePdfService,
        LocaleService $localeService,
        BankService $bankService,
        ClientRepository $clientRepository,
        SupplierRepository $supplierRepository
    ) {
        $this->qrPaymentService = $qrPaymentService;
        $this->invoiceService = $invoiceService;
        $this->invoicePdfService = $invoicePdfService;
        $this->localeService = $localeService;
        $this->bankService = $bankService;
        $this->clientRepository = $clientRepository;
        $this->supplierRepository = $supplierRepository;
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
        $clients = $this->clientRepository->getClientsForDropdown();
        
        // Get suppliers for dropdown
        $suppliers = $this->supplierRepository->getSuppliersForDropdown();
        
        // Get payment methods for dropdown
        $paymentMethods = PaymentMethod::all()->pluck('slug', 'id')->toArray();
        
        // Set invoice products
        $invoiceProducts = [];

        // Load all active invoice statuses
        $statuses = Status::pluck('name', 'id')->toArray();
        
        // Get tax rates for dropdown
        $taxRates = Tax::where('slug', 'dph')
            ->pluck('rate', 'id')
            ->toArray();

        // Get banks for dropdown
        $banks = $this->bankService->getBanksForDropdown();

        // Get banksData for JD bank-fields.js
        $banksData = $this->bankService->getBanksForJs();
        
        // Get suggested invoice number
        $suggestedNumber = $this->invoiceService->getNextInvoiceNumber();

        // Preselected client if client_id is provided in request
        $selectedClient = null;
        if ($request->has('client_id')) {
            try {
                $selectedClient = Client::where('user_id', Auth::id())
                    ->findOrFail($request->client_id);
            } catch (ModelNotFoundException $e) {
                Log::error('Error loading client: ' . $e->getMessage(), [
                    'user_id' => Auth::id()
                ]);
                
                // just show flash message
                session()->flash('error', __('clients.messages.not_found'));
            }
        }
        
        // If no specific client is requested, use default client
        if (!$selectedClient) {
            $selectedClient = $this->clientRepository->getDefaultClient();
        }
        
        // Find default supplier
        $defaultSupplier = null;
        if ($request->has('supplier_id')) {
            try {
                $defaultSupplier = Supplier::where('user_id', Auth::id())
                    ->findOrFail($request->supplier_id);
            } catch (ModelNotFoundException $e) {
                Log::error('Error loading supplier: ' . $e->getMessage(), [
                    'user_id' => Auth::id()
                ]);
            }
        }
        
        // If no specific supplier is requested, use default supplier
        if (!$defaultSupplier) {
            $defaultSupplier = $this->supplierRepository->getDefaultSupplier();
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
        $itemUnits = $this->invoiceService->getItemUnits();
        
        // Get fields for invoice form from trait
        $fields = $this->getInvoiceFields($clients, $suppliers, $paymentMethods, $statuses);
        
        return view('frontend.invoices.create', compact(
            'userLoggedIn', 'fields', 'clients', 
            'suppliers', 'userInfo', 'clientInfo', 'paymentMethods', 
            'suggestedNumber', 'statuses', 'defaultSupplier', 'banks',
            'banksData', 'taxRates', 'itemUnits', 'invoiceProducts'
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
        $banks = $this->bankService->getBanksForDropdown();

        // Get banksData for JD bank-fields.js
        $banksData = $this->bankService->getBanksForJs();
        
        // Get suggested invoice number
        $suggestedNumber = $this->invoiceService->getNextInvoiceNumber();

        // Get Invoice products
        $invoiceProducts = [];
        
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
        $itemUnits = $this->invoiceService->getItemUnits();
        
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
        try {
            // Get invoice with related models
            $invoice = Invoice::with('client', 'supplier', 'paymentStatus', 'paymentMethod')->findOrFail($id);
            
            // Check if user has permission to edit this invoice
            if ($invoice->user_id != Auth::id()) {
                return redirect()
                    ->route('frontend.invoices', ['lang' => app()->getLocale()])
                    ->with('error', __('invoices.messages.edit_error_unauthorized'));
            }
            
            $user = Auth::user();
            $userLoggedIn = Auth::check();
            
            // Populate client fields from relation if available
            if ($invoice->client) {
                $invoice->client_name = $invoice->client_name ?? $invoice->client->name;
                $invoice->client_email = $invoice->client_email ?? $invoice->client->email;
                $invoice->client_phone = $invoice->client_phone ?? $invoice->client->phone;
                $invoice->client_street = $invoice->client_street ?? $invoice->client->street;
                $invoice->client_city = $invoice->client_city ?? $invoice->client->city;
                $invoice->client_zip = $invoice->client_zip ?? $invoice->client->zip;
                $invoice->client_country = $invoice->client_country ?? $invoice->client->country;
                $invoice->client_ico = $invoice->client_ico ?? $invoice->client->ico;
                $invoice->client_dic = $invoice->client_dic ?? $invoice->client->dic;
            }

            // Populate supplier fields from relation if available
            if ($invoice->supplier) {
                $invoice->name = $invoice->name ?? $invoice->supplier->name;
                $invoice->email = $invoice->email ?? $invoice->supplier->email;
                $invoice->phone = $invoice->phone ?? $invoice->supplier->phone;
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
                // If no supplier is assigned, try to use default supplier
                $defaultSupplier = $this->supplierRepository->getDefaultSupplier();
                
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
                    // No supplier, use authenticated user info as fallback
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
            $clients = $this->clientRepository->getClientsForDropdown();
            
            // Get suppliers for dropdown
            $suppliers = $this->supplierRepository->getSuppliersForDropdown();

            // Get payment methods for dropdown
            $paymentMethods = PaymentMethod::all()->pluck('slug', 'id')->toArray();
            
            // Load all active invoice statuses
            $statuses = Status::pluck('name', 'id')->toArray();

            // Get tax rates for dropdown
            $taxRates = Tax::where('slug', 'dph')
                ->pluck('rate', 'id')
                ->toArray();

            // Get banks for dropdown
            $banks = $this->bankService->getBanksForDropdown();

            // Get banksData for JD bank-fields.js
            $banksData = $this->bankService->getBanksForJs();

            // Get item units for invoice items
            $itemUnits = $this->invoiceService->getItemUnits();

            // Get Invoice products
            $invoiceProducts = InvoiceProduct::where('invoice_id', $id)
                ->with(['product'])
                ->get()
                ->toArray();

            Log::info('Invoice products: ', $invoiceProducts);
            
            // Get fields for invoice form from trait
            $fields = $this->getInvoiceFields($clients, $suppliers, $paymentMethods, $statuses);
            
            return view('frontend.invoices.edit', compact(
                'invoice', 'userLoggedIn', 'fields',
                'paymentMethods', 'clients', 'suppliers', 'statuses',
                'user', 'banks', 'banksData', 'taxRates', 'itemUnits',
                'invoiceProducts'
            ));
        } catch (ModelNotFoundException $e) {
            Log::error('Invoice not found: ' . $e->getMessage());
            return redirect()
                ->route('frontend.invoices', ['lang' => app()->getLocale()])
                ->with('error', __('invoices.messages.not_found'));
        } catch (\Exception $e) {
            Log::error('Error loading invoice for editing: ' . $e->getMessage());
            return redirect()
                ->route('frontend.invoices', ['lang' => app()->getLocale()])
                ->with('error', __('invoices.messages.edit_error'));
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
        try {
            // Get validated data
            $data = $request->validated();
            
            // Add user_id to data
            $data['user_id'] = Auth::id();
        
            if (empty($data['client_id']) && !empty($data['client_name']) && strlen($data['client_name']) >= 3) {
                // Create new client
                $client = $this->clientRepository->create([
                    'name' => $data['client_name'],
                    'street' => $data['client_street'] ?? '',
                    'city' => $data['client_city'] ?? '',
                    'zip' => $data['client_zip'] ?? '',
                    'country' => $data['client_country'] ?? 'CZ',
                    'ico' => $data['client_ico'] ?? '',
                    'dic' => $data['client_dic'] ?? '',
                    'email' => $data['client_email'] ?? '',
                    'phone' => $data['client_phone'] ?? '',
                ]);
                
                // Add new client ID to invoice data
                $data['client_id'] = $client->id;
            }
        
            if (empty($data['supplier_id']) && !empty($data['name']) && strlen($data['name']) >= 3) {
                // Create new supplier
                $supplier = $this->supplierRepository->create([
                    'name' => $data['name'],
                    'street' => $data['street'] ?? '',
                    'city' => $data['city'] ?? '',
                    'zip' => $data['zip'] ?? '',
                    'country' => $data['country'] ?? 'CZ',
                    'ico' => $data['ico'] ?? '',
                    'dic' => $data['dic'] ?? '',
                    'email' => $data['email'] ?? '',
                    'phone' => $data['phone'] ?? '',
                    'account_number' => $data['account_number'] ?? '',
                    'bank_code' => $data['bank_code'] ?? '',
                    'bank_name' => $data['bank_name'] ?? '',
                    'iban' => $data['iban'] ?? '',
                    'swift' => $data['swift'] ?? '',
                ]);
                
                // Add new supplier ID to invoice data
                $data['supplier_id'] = $supplier->id;
            }

            // Create invoice
            $invoice = Invoice::create($data);
                
            // Get invoice products from request
            $invoiceProducts = $request->input('invoice-products');
            
            // Decode JSON if it's a string
            if (is_string($invoiceProducts)) {
                $invoiceProducts = json_decode($invoiceProducts, true);
            }

            // Add products to invoice
            if (is_array($invoiceProducts) && !empty($invoiceProducts)) {
                $this->invoiceService->saveInvoiceProducts($invoice, $invoiceProducts);
            }

            // Recalculate total amount
            $invoice->calculateTotalAmount();
            
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
            $locale = $this->localeService->determineLocale($locale);
            $data['lang'] = $locale;
        
            // Convert due_in to integer
            if (isset($data['due_in'])) {
                $data['due_in'] = (int)$data['due_in'];
            }
        
            // Process invoice products
            $invoiceProducts = $request->input('invoice-products');
            
            // Make sure products JSON is included in cached data
            if (is_string($invoiceProducts)) {
                $data['invoice-products'] = $invoiceProducts;
            } else {
                $data['invoice-products'] = json_encode($invoiceProducts);
            }
            
            // Generate token and store invoice data in cache
            $token = $this->invoiceService->storeTemporaryInvoice($data);

            // Save token to session for later use
            Session::put('last_guest_invoice_token', $token);
            Session::put('last_guest_invoice_number', $data['invoice_vs']);
            Session::put('last_guest_invoice_expires', now()->addMinutes(10)->timestamp);
            
            // Create temporary invoice object for QR code
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
                if ((!empty($tempInvoice->account_number) && !empty($tempInvoice->bank_code)) || !empty($tempInvoice->iban)) {
                    $qrCodeBase64 = $this->qrPaymentService->generateQrCodeBase64($tempInvoice);
                }
            } catch (\Exception $e) {
                Log::error('Error while generating QR code: ' . $e->getMessage());
            }
            
            // Return success response
            return response()->json([
                'success' => true,
                'message' => __('invoices.messages.created_guest'),
                'invoice_number' => $data['invoice_vs'],
                'download_url' => route('frontend.invoice.download.token', 
                [
                    'lang' => $locale,
                    'token' => $token
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
                // Silent ignore of static file requests
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
            $invoice = Invoice::with(['supplier', 'client', 'paymentMethod', 'paymentStatus'])
                ->where('user_id', Auth::id())
                ->findOrFail($id);

            // Populate supplier fields if supplier exists
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
        } catch (\Exception $e) {
            Log::error('Error viewing invoice: ' . $e->getMessage());
            
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
                ->findOrFail($id);
            
            // Check if user has permission to access this invoice
            if ($invoice->user_id != Auth::id()) {
                return redirect()->back()->with('error', __('invoices.messages.access_denied'));
            }

            // Set locale based on request
            $requestLocale = $request->get('lang');
            
            // Generate PDF
            $pdf = $this->invoicePdfService->generatePdf($invoice, $requestLocale);
            
            // Preview or download
            if ($request->has('preview')) {
                return $pdf->stream('faktura-'.$invoice->invoice_vs.'.pdf');
            }
        
            // Download with cookie for locale
            $locale = $this->localeService->determineLocale($requestLocale);
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
     * @param Request $request
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function downloadWithToken(Request $request)
    {
        try {
            $token = $request->get('token');
            if (!$token) {
                $token = Session::get('last_guest_invoice_token');
                if (!$token) {
                    abort(404, __('invoices.messages.not_found'));
                }
            }
            
            Log::info('Starting PDF generation with token: ' . substr($token, 0, 8) . '...');
            
            // Get invoice data from cache
            $invoiceData = $this->invoiceService->getTemporaryInvoiceByToken($token);
            
            if (!$invoiceData) {
                abort(404, __('invoices.messages.expired'));
            }

            // Set locale based on request
            $requestLocale = $request->get('lang');
            
            // Generate PDF from data
            $pdf = $this->invoicePdfService->generatePdfFromData($invoiceData, $requestLocale);
            
            $filename = 'faktura-' . ($invoiceData['invoice_vs'] ?? date('YmdHis')) . '.pdf';
            
            // Preview or download
            if ($request->has('preview')) {
                return $pdf->stream($filename);
            }
            
            // Set locale cookie
            $locale = $this->localeService->determineLocale($requestLocale, $invoiceData['lang'] ?? null);
            $response = $pdf->download($filename);
            return $response->cookie('locale', $locale, 60 * 24 * 30); // 30 days
        } catch (\Exception $e) {
            Log::error('Error while generating invoice PDF: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            abort(500, __('invoices.messages.pdf_error'));
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
            // Find invoice and check ownership
            $invoice = Invoice::where('user_id', Auth::id())->findOrFail($id);
            
            // Additional check for security
            if ($invoice->user_id != Auth::id()) {
                return redirect()
                    ->route('frontend.invoices', ['lang' => app()->getLocale()])
                    ->with('error', __('invoices.messages.update_error_unauthorized'));
            }
            
            // Get validated data
            $data = $request->validated();
        
            // Create new client if needed
            if (empty($data['client_id']) && !empty($data['client_name']) && strlen($data['client_name']) >= 3) {
                // Create new client using repository
                $client = $this->clientRepository->create([
                    'name' => $data['client_name'],
                    'street' => $data['client_street'] ?? '',
                    'city' => $data['client_city'] ?? '',
                    'zip' => $data['client_zip'] ?? '',
                    'country' => $data['client_country'] ?? 'CZ',
                    'ico' => $data['client_ico'] ?? '',
                    'dic' => $data['client_dic'] ?? '',
                    'email' => $data['client_email'] ?? '',
                    'phone' => $data['client_phone'] ?? '',
                    'description' => $data['client_description'] ?? '',
                ]);
                
                // Set client
                $data['client_id'] = $client->id;
            }
        
            // Create new supplier if needed
            if (empty($data['supplier_id']) && !empty($data['name']) && strlen($data['name']) >= 3) {
                // Create new supplier using repository
                $supplier = $this->supplierRepository->create([
                    'name' => $data['name'],
                    'street' => $data['street'] ?? '',
                    'city' => $data['city'] ?? '',
                    'zip' => $data['zip'] ?? '',
                    'country' => $data['country'] ?? 'CZ',
                    'ico' => $data['ico'] ?? '',
                    'dic' => $data['dic'] ?? '',
                    'email' => $data['email'] ?? '',
                    'phone' => $data['phone'] ?? '',
                    'description' => $data['description'] ?? '',
                    'account_number' => $data['account_number'] ?? '',
                    'bank_code' => $data['bank_code'] ?? '',
                    'bank_name' => $data['bank_name'] ?? '',
                    'iban' => $data['iban'] ?? '',
                    'swift' => $data['swift'] ?? '',
                ]);
                
                // Set supplier
                $data['supplier_id'] = $supplier->id;
            }
            
            // Update invoice
            $invoice->update($data);
            
            // Delete existing products
            $invoice->invoiceProducts()->delete();
            
            // Get invoice products from request
            $invoiceProducts = $request->input('invoice-products');
            
            // Decode JSON if it's a string
            if (is_string($invoiceProducts)) {
                $invoiceProducts = json_decode($invoiceProducts, true);
            }
            
            // Add products to invoice
            if (is_array($invoiceProducts) && !empty($invoiceProducts)) {
                $this->invoiceService->saveInvoiceProducts($invoice, $invoiceProducts);
            }

            // Recalculate total amount
            $invoice->calculateTotalAmount();
            
            return redirect()
                ->route('frontend.invoice.show', ['id' => $invoice->id, 'lang' => app()->getLocale()])
                ->with('success', __('invoices.messages.updated'));
        } catch (ModelNotFoundException $e) {
            Log::error('Invoice not found during update: ' . $e->getMessage());
            return back()
                ->withInput()
                ->with('error', __('invoices.messages.not_found'));
        } catch (\Exception $e) {
            Log::error('Error updating invoice: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return back()
                ->withInput()
                ->with('error', __('invoices.messages.update_error') . ' ' . $e->getMessage());
        }
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
                return redirect()->route('home')
                    ->with('error', __('invoices.messages.invalid_token'));
            }

            // Delete invoice data from cache
            $this->invoiceService->deleteTemporaryInvoice($token);
            
            // Clear token from session
            Session::forget('last_guest_invoice_token');
            Session::forget('last_guest_invoice_number');
            Session::forget('last_guest_invoice_expires');
            
            return redirect()->route('home')
                ->with('success', __('invoices.messages.deleted_guest'));
        } catch (\Exception $e) {
            Log::error('Error deleting guest invoice', [
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
            $success = $this->invoiceService->markInvoiceAsPaid($id);
            
            if ($success) {
                return back()->with('success', __('invoices.messages.marked_as_paid'));
            } else {
                return back()->with('error', __('invoices.messages.update_error'));
            }
        } catch (\Exception $e) {
            Log::error('Error marking invoice as paid: ' . $e->getMessage());
            return back()->with('error', __('invoices.messages.update_error'));
        }
    }
}
