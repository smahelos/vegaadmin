<?php

namespace App\Application\Invoice\Services\Specialized;

use App\Application\Invoice\Contracts\InvoiceFormApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceLimitApplicationServiceInterface;
use App\Application\Product\Contracts\ProductApplicationServiceInterface;
use App\Application\Product\Contracts\InvoiceProductReadRepositoryInterface;
use App\Application\Party\Contracts\PartyApplicationServiceInterface;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Application\Invoice\Contracts\InvoiceReadRepositoryInterface;
use App\Domain\User\Contracts\UserReadRepositoryInterface;
use App\Domain\Shared\Tax\Contracts\TaxDtoReadRepositoryInterface;
use App\Domain\Payment\Contracts\PaymentMethodDtoReadRepositoryInterface;
use App\Domain\Payment\Contracts\BankServiceInterface;
use App\Domain\Shared\Status\Contracts\StatusDtoRepositoryInterface;
use App\Application\Invoice\DTO\InvoiceFormDTO;
use App\Application\Shared\Form\DTO\FieldSetDTO;
use App\Application\Invoice\Form\InvoiceCreateFieldSetFactory;
use Illuminate\Support\Collection;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

/**
 * Service for invoice form-related operations
 */
class InvoiceFormApplicationService implements InvoiceFormApplicationServiceInterface
{
    public function __construct(
        private UserReadRepositoryInterface $userReadRepository,
        private ProductApplicationServiceInterface $productApplicationService,
        private InvoiceProductReadRepositoryInterface $productReadRepository,
        private PartyApplicationServiceInterface $partyApplicationService,
        private BankServiceInterface $bankService,
        private PaymentMethodDtoReadRepositoryInterface $paymentMethodRepository,
        private TaxDtoReadRepositoryInterface $taxRepository,
        private InvoiceReadRepositoryInterface $invoiceReadRepository,
        private StatusDtoRepositoryInterface $statusRepository,
        private InvoiceLimitApplicationServiceInterface $invoiceLimitService
    ) {}

    /**
     * Get comprehensive form data for invoice creation/editing
     */
    public function getFormData(UserId $userId, ?InvoiceId $invoiceId = null): array
    {
        $userIdInt = $userId->toInt();
        
        $formData = [
            'products' => $this->getProducts($userId),
            'clients' => $this->getClients($userId),
            'suppliers' => $this->getSuppliers($userId),
            'banks' => $this->getBanks($userId),
            'paymentMethods' => $this->getPaymentMethods($userId),
            'taxes' => $this->getTaxes($userId),
            'defaults' => $this->loadDefaults($userId),
        ];

        // If editing existing invoice, load its data
        if ($invoiceId) {
            $invoice = $this->invoiceReadRepository->findById($invoiceId->getValue());
            $formData['invoice'] = $invoice;
        }

        return $formData;
    }

    /**
     * Prepare form data with defaults and options
     */
    public function prepareFormData(UserId $userId, ?InvoiceId $invoiceId = null): array
    {
        $formData = $this->getFormData($userId, $invoiceId);
        
        // Add additional preparation logic here
        $formData['selectOptions'] = $this->getSelectOptions($userId);
        $formData['itemUnits'] = $this->getItemUnits();
        
        return $formData;
    }

    /**
     * Load default values for new invoice
     */
    public function loadDefaults(UserId $userId): array
    {
        $user = $this->userReadRepository->findUserById($userId);
        $userIdInt = $userId->toInt();
        
        return [
            'currency' => 'CZK',
            'language' => 'cs',
            'due_date_days' => 14,
            'invoice_number' => $this->generateNextInvoiceNumber($userIdInt),
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(14)->format('Y-m-d'),
        ];
    }

    /**
     * Get select options for form dropdowns
     */
    public function getSelectOptions(UserId $userId): array
    {
        $userIdInt = $userId->toInt();
        
        return [
            'products' => $this->productApplicationService->getProductsDropdown($userIdInt),
            'clients' => $this->partyApplicationService->clientOptions($userIdInt),
            'suppliers' => $this->partyApplicationService->supplierOptions($userIdInt),
            'banks' => $this->bankService->getBanksForDropdown(),
            'paymentMethods' => $this->getPaymentMethodsOptions(),
            'taxes' => $this->getTaxesOptions(),
        ];
    }

    /**
     * Get available products for user
     */
    public function getProducts(UserId $userId): Collection
    {
        $userIdInt = $userId->toInt();
        return $this->productApplicationService->listProducts($userIdInt);
    }

    /**
     * Get available clients for user
     */
    public function getClients(UserId $userId): Collection
    {
        $userIdInt = $userId->toInt();
        $clients = $this->partyApplicationService->clientOptions($userIdInt);
        return collect($clients);
    }

    /**
     * Get available suppliers for user
     */
    public function getSuppliers(UserId $userId): Collection
    {
        $userIdInt = $userId->toInt();
        $suppliers = $this->partyApplicationService->supplierOptions($userIdInt);
        return collect($suppliers);
    }

    /**
     * Get available banks for user
     */
    public function getBanks(UserId $userId): Collection
    {
        $banks = $this->bankService->getBanksForDropdown();
        return collect($banks);
    }

    /**
     * Get available payment methods for user
     */
    public function getPaymentMethods(UserId $userId): Collection
    {
        $paymentMethods = $this->paymentMethodRepository->all();
        return collect($paymentMethods);
    }

    /**
     * Get available taxes for user
     */
    public function getTaxes(UserId $userId): Collection
    {
        $taxes = $this->taxRepository->getAllTaxes();
        return collect($taxes);
    }

    /**
     * Get payment methods options for dropdown
     */
    private function getPaymentMethodsOptions(): array
    {
        return $this->paymentMethodRepository->getAllForDropdown();
    }

    /**
     * Get taxes options for dropdown
     */
    private function getTaxesOptions(): array
    {
        return $this->taxRepository->getAllTaxesForSelect();
    }

    /**
     * Generate next invoice number for user
     */
    private function generateNextInvoiceNumber(int $userId): string
    {
        $currentYear = date('Y');
        if ($userId <= 0) { 
            return $currentYear . '0001'; 
        }
        
        $lastCurrentYear = $this->invoiceReadRepository->findLastInvoiceForYear($userId, (int)$currentYear);
        
        if ($lastCurrentYear && strlen($lastCurrentYear->invoice_vs) >= 8) {
            $sequencePart = substr($lastCurrentYear->invoice_vs, 4);
            if (ctype_digit($sequencePart)) {
                $nextSeq = (int)$sequencePart + 1;
                return $currentYear . str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);
            }
        }
        
        return $currentYear . '0001';
    }

    /**
     * Prepare data for invoice creation form (legacy compatibility)
     */
    public function prepareCreateData(int $userId, Request $request): InvoiceFormDTO
    {
        // Get clients & suppliers options
        $clients = $this->partyApplicationService->clientOptions($userId);
        $suppliers = $this->partyApplicationService->supplierOptions($userId);

        // Payment methods
        $paymentMethods = $this->paymentMethodRepository->getAllForDropdown();

        // Initial empty products list
        $invoiceProducts = [];

        // Statuses
        $statuses = $this->statusRepository->getAllForDropdown();

        // Tax rates
        $taxRates = $this->taxRepository->getDphRatesForDropdown();

        // Banks
        $banks = $this->bankService->getBanksForDropdown();
        $banksData = $this->bankService->getBanksForJs();

        // Suggested invoice number
        $suggestedNumber = $this->getNextInvoiceNumber($userId);

        // Selected client (explicit)
        $selectedClient = null;
        if ($request->has('client_id')) {
            try {
                $selectedClient = $this->partyApplicationService->findClient($userId, (int)$request->client_id);
            } catch (ModelNotFoundException $e) {
                Log::error('Error loading client: ' . $e->getMessage(), [
                    'user_id' => $userId
                ]);
                session()->flash('error', __('clients.messages.not_found'));
            }
        }

        if (!$selectedClient) {
            $selectedClient = $this->partyApplicationService->defaultClient($userId);
        }

        // Selected supplier (explicit)
        $defaultSupplier = null;
        if ($request->has('supplier_id')) {
            try {
                $defaultSupplier = $this->partyApplicationService->findSupplier($userId, (int)$request->supplier_id);
            } catch (ModelNotFoundException $e) {
                Log::error('Error loading supplier: ' . $e->getMessage(), [
                    'user_id' => $userId
                ]);
            }
        }

        if (!$defaultSupplier) {
            $defaultSupplier = $this->partyApplicationService->defaultSupplier($userId);
        }

        // Supplier (user) info prefill
        $userInfo = $this->buildSupplierUserInfo($defaultSupplier, $userId);

        // Client info prefill
        $clientInfo = $this->buildClientInfo($selectedClient);

        // Item units
        $itemUnits = $this->getItemUnits();

        // Limits data
        $limitsData = $this->getInvoicesLimit($userId);

        $fieldSet = app(InvoiceCreateFieldSetFactory::class)->build(
            $clients,
            $suppliers,
            $paymentMethods,
            $statuses
        );

        return new InvoiceFormDTO($fieldSet, [
            'clients' => $clients,
            'suppliers' => $suppliers,
            'paymentMethods' => $paymentMethods,
            'invoiceProducts' => $invoiceProducts,
            'statuses' => $statuses,
            'taxRates' => $taxRates,
            'banks' => $banks,
            'banksData' => $banksData,
            'suggestedNumber' => $suggestedNumber,
            'selectedClient' => $selectedClient,
            'defaultSupplier' => $defaultSupplier,
            'userInfo' => $userInfo,
            'clientInfo' => $clientInfo,
            'itemUnits' => $itemUnits,
            'limitsData' => $limitsData,
            'userLoggedIn' => true,
        ]);
    }

    /**
     * Prepare data for invoice editing form (legacy compatibility)
     */
    public function prepareEditData(int $userId, int $invoiceId, string $locale): InvoiceFormDTO
    {
// Get UserDTO from int $userId
        $userIdVO = UserId::fromInt($userId);
        $user = $this->userReadRepository->findUserById($userIdVO);

        // Get invoice model or fail - we need to load relations manually
        // Loading Eloquent model for Application DTO -> InvoiceFormDTO for UI, not Domain layer InvoiceDTO
        // In Application layer we work with Eloquent model on top of UI for eager loading and dynamic accessors (for example blade accessors)
        // In Domain layer we work with InvoiceDTO only
        $invoice = $this->invoiceReadRepository->findForUser($userId, $invoiceId);
        if (!$invoice) {
            throw new ModelNotFoundException('Invoice not accessible for user');
        }
        $invoice->load(['client', 'supplier', 'paymentStatus', 'paymentMethod']);

        // Get related parties - we need full DTOs for edit view (for example to show client email)
        $client = $invoice->client;
        $supplier = $invoice->supplier;

        // Optional related DTOs (null-safe)
        // We can use DTOs from relations if loaded, otherwise try to load via Party service (with permission check)
        if (!$client && !empty($invoice->client_id)) {
            try { $client = $this->partyApplicationService->findClient($userId, (int)$invoice->client_id); } catch (ModelNotFoundException $e) { $client = null; }
        }
        if (!$supplier && !empty($invoice->supplier_id)) {
            try { $supplier = $this->partyApplicationService->findSupplier($userId, (int)$invoice->supplier_id); } catch (ModelNotFoundException $e) { $supplier = null; }
        }

        // Dropdown and related data
        $clients = $this->partyApplicationService->clientOptions($userId);
        $suppliers = $this->partyApplicationService->supplierOptions($userId);
        $paymentMethods = $this->paymentMethodRepository->getAllForDropdown();
        $statuses = $this->statusRepository->getAllForDropdown();
        $taxRates = $this->taxRepository->getDphRatesForDropdown();
        $banks = $this->bankService->getBanksForDropdown();
        $banksData = $this->bankService->getBanksForJs();
        $itemUnits = $this->getItemUnits();
        // Prefill existing invoice products for edit UI
        $invoiceProducts = $this->productReadRepository->allForInvoice($invoiceId);
        $limitsData = $this->getInvoicesLimit($userId);

        $fieldSet = app(InvoiceCreateFieldSetFactory::class)->build(
            $clients,
            $suppliers,
            $paymentMethods,
            $statuses
        );

        return new InvoiceFormDTO($fieldSet, [
            'invoice' => $invoice,
            'clients' => $clients,
            'client' => $client,
            'suppliers' => $suppliers,
            'supplier' => $supplier,
            'paymentMethods' => $paymentMethods,
            'statuses' => $statuses,
            'taxRates' => $taxRates,
            'banks' => $banks,
            'banksData' => $banksData,
            'itemUnits' => $itemUnits,
            'invoiceProducts' => $invoiceProducts,
            'limitsData' => $limitsData,
            'user' => $user,
            'userLoggedIn' => true,
        ]);
    }

    /**
     * Prepare data for invoice display (legacy compatibility)
     */
    public function prepareShowData(int $userId, int $invoiceId): InvoiceFormDTO
    {
        $invoice = $this->invoiceReadRepository->findForUser($userId, $invoiceId);
        if (!$invoice) { 
            throw new ModelNotFoundException('Invoice not accessible for user'); 
        }
        $invoice->load(['supplier', 'client', 'paymentMethod', 'paymentStatus']);

        // Get related parties - we need full DTOs for edit view (for example to show client email)
        $client = $invoice->client;
        $supplier = $invoice->supplier;

        // Optional related DTOs (null-safe)
        // We can use DTOs from relations if loaded, otherwise try to load via Party service (with permission check)
        if (!$client && !empty($invoice->client_id)) {
            try { 
                $client = $this->partyApplicationService->findClient($userId, (int)$invoice->client_id); 
            } catch (ModelNotFoundException $e) { 
                $client = null; 
            }
        }
        if (!$supplier && !empty($invoice->supplier_id)) {
            try { 
                $supplier = $this->partyApplicationService->findSupplier($userId, (int)$invoice->supplier_id); 
            } catch (ModelNotFoundException $e) { 
                $supplier = null; 
            }
        }

        $invoiceProducts = $this->productReadRepository->allForInvoice($invoiceId);
        $limitsData = $this->getInvoicesLimit($userId);
        
        // For now, return a simplified DTO with empty FieldSetDTO
        $fieldSetDto = new FieldSetDTO([]);
        $meta = [
            'invoice' => $invoice,
            'limitsData' => $limitsData,
            'paymentAmount' => $invoice->payment_amount_money,
            'subtotal' => $invoice->subtotal_money,
            'totalTax' => $invoice->total_tax_money,
            'client' => $client,
            'supplier' => $supplier,
            'invoiceProducts' => $invoiceProducts,
        ];
        
        return new InvoiceFormDTO($fieldSetDto, $meta);
    }

    /**
     * Get next invoice number for user (legacy compatibility)
     */
    public function getNextInvoiceNumber(int $userId): string
    {
        return $this->generateNextInvoiceNumber($userId);
    }

    /**
     * Get item units for invoice items (legacy compatibility)
     */
    public function getItemUnits(): array
    {
        return [
            'hours' => __('invoices.units.hours'),
            'days' => __('invoices.units.days'),
            'pieces' => __('invoices.units.pieces'),
        ];
    }

    /**
     * Build supplier user info (legacy compatibility)
     */
    private function buildSupplierUserInfo($defaultSupplier, int $userId): array
    {
        // TODO: Implement this method with proper logic from original service
        return [];
    }

    /**
     * Build client info (legacy compatibility)
     */
    private function buildClientInfo($selectedClient): array
    {
        // TODO: Implement this method with proper logic from original service
        return [];
    }

    /**
     * Get invoices limit (legacy compatibility)
     */
    private function getInvoicesLimit(int $userId): array
    {
        $limitInfo = $this->invoiceLimitService->getLimitInfo(new UserId($userId));
        return $limitInfo;
    }
}
