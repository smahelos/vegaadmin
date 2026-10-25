<?php

namespace App\Application\Invoice\Services\Specialized;

use App\Application\Invoice\Contracts\InvoiceFormApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceLimitApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceReadRepositoryInterface;
use App\Application\Invoice\DTO\InvoiceFormDTO;
use App\Application\Invoice\Form\InvoiceCreateFieldSetFactory;
use App\Application\Party\Contracts\PartyApplicationServiceInterface;
use App\Application\Product\Contracts\InvoiceProductReadRepositoryInterface;
use App\Application\Shared\Form\DTO\FieldSetDTO;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Payment\Contracts\BankServiceInterface;
use App\Domain\Payment\Contracts\PaymentMethodDtoReadRepositoryInterface;
use App\Domain\Shared\Status\Contracts\StatusDtoRepositoryInterface;
use App\Domain\Shared\Tax\Contracts\TaxDtoReadRepositoryInterface;
use App\Domain\User\Contracts\UserReadRepositoryInterface;
use App\Domain\User\ValueObjects\UserId;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Service for invoice form-related operations
 */
class InvoiceFormApplicationService implements InvoiceFormApplicationServiceInterface
{
    public function __construct(
        private UserReadRepositoryInterface $userReadRepository,
        private InvoiceProductReadRepositoryInterface $productReadRepository,
        private PartyApplicationServiceInterface $partyApplicationService,
        private BankServiceInterface $bankService,
        private PaymentMethodDtoReadRepositoryInterface $paymentMethodRepository,
        private TaxDtoReadRepositoryInterface $taxRepository,
        private InvoiceReadRepositoryInterface $invoiceReadRepository,
        private StatusDtoRepositoryInterface $statusRepository,
        private InvoiceLimitApplicationServiceInterface $invoiceLimitService,
        private InvoiceServiceInterface $invoiceService,
    ) {}

    /**
     * Generate next invoice number for user
     */
    private function generateNextInvoiceNumber(int $userId): string
    {
        // Guests have no numbering history: first number of the year
        if ($userId <= 0) {
            return InvoiceNumber::generateNext()->getNumber();
        }

        return $this->invoiceService->generateInvoiceNumber(UserId::fromInt($userId), (int) date('Y'));
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
                $selectedClient = $this->partyApplicationService->findClient($userId, (int) $request->client_id);
            } catch (ModelNotFoundException $e) {
                Log::error('Error loading client: ' . $e->getMessage(), [
                    'user_id' => $userId,
                ]);
                session()->flash('error', __('clients.messages.not_found'));
            }
        }

        if (! $selectedClient) {
            $selectedClient = $this->partyApplicationService->defaultClient($userId);
        }

        // Selected supplier (explicit)
        $defaultSupplier = null;
        if ($request->has('supplier_id')) {
            try {
                $defaultSupplier = $this->partyApplicationService->findSupplier($userId, (int) $request->supplier_id);
            } catch (ModelNotFoundException $e) {
                Log::error('Error loading supplier: ' . $e->getMessage(), [
                    'user_id' => $userId,
                ]);
            }
        }

        if (! $defaultSupplier) {
            $defaultSupplier = $this->partyApplicationService->defaultSupplier($userId);
        }

        // Supplier (user) info prefill
        $userInfo = $this->buildSupplierUserInfo($defaultSupplier, $userId);

        // Client info prefill
        $clientInfo = $this->buildClientInfo($selectedClient);

        // Limits data
        $limitsData = $this->getInvoicesLimit($userId);

        $fieldSet = app(InvoiceCreateFieldSetFactory::class)->build(
            $clients,
            $suppliers,
            $paymentMethods,
            $statuses,
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
            'limitsData' => $limitsData,
            'userLoggedIn' => true,
        ]);
    }

    /**
     * Prepare data for invoice editing form (legacy compatibility)
     */
    public function prepareEditData(
        int $userId,
        int $invoiceId,
        string $locale,
    ): InvoiceFormDTO {
        // Get UserDTO from int $userId
        $userIdVO = UserId::fromInt($userId);
        $user = $this->userReadRepository->findUserById($userIdVO);

        // Get invoice model or fail - we need to load relations manually
        // Loading Eloquent model for Application DTO -> InvoiceFormDTO for UI, not Domain layer InvoiceDTO
        // In Application layer we work with Eloquent model on top of UI for eager loading and dynamic accessors (for example blade accessors)
        // In Domain layer we work with InvoiceDTO only
        $invoice = $this->invoiceReadRepository->findForUser($userId, $invoiceId);
        if (! $invoice) {
            throw new ModelNotFoundException('Invoice not accessible for user');
        }
        $invoice->load([
            'client',
            'supplier',
            'paymentStatus',
            'paymentMethod',
        ]);

        // Get related parties - we need full DTOs for edit view (for example to show client email)
        $client = $invoice->client;
        $supplier = $invoice->supplier;

        // Optional related DTOs (null-safe)
        // We can use DTOs from relations if loaded, otherwise try to load via Party service (with permission check)
        if (! $client && ! empty($invoice->client_id)) {
            try {
                $client = $this->partyApplicationService->findClient($userId, (int) $invoice->client_id);
            } catch (ModelNotFoundException $e) {
                $client = null;
            }
        }
        if (! $supplier && ! empty($invoice->supplier_id)) {
            try {
                $supplier = $this->partyApplicationService->findSupplier($userId, (int) $invoice->supplier_id);
            } catch (ModelNotFoundException $e) {
                $supplier = null;
            }
        }

        // Dropdown and related data
        $clients = $this->partyApplicationService->clientOptions($userId);
        $suppliers = $this->partyApplicationService->supplierOptions($userId);
        $paymentMethods = $this->paymentMethodRepository->getAllForDropdown();
        $statuses = $this->statusRepository->getAllForDropdown();
        $taxRates = $this->taxRepository->getDphRatesForDropdown();
        $banks = $this->bankService->getBanksForDropdown();
        $banksData = $this->bankService->getBanksForJs();
        // Prefill existing invoice products for edit UI
        $invoiceProducts = $this->productReadRepository->allForInvoice($invoiceId);
        $limitsData = $this->getInvoicesLimit($userId);

        $fieldSet = app(InvoiceCreateFieldSetFactory::class)->build(
            $clients,
            $suppliers,
            $paymentMethods,
            $statuses,
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
        if (! $invoice) {
            throw new ModelNotFoundException('Invoice not accessible for user');
        }
        $invoice->load([
            'supplier',
            'client',
            'paymentMethod',
            'paymentStatus',
        ]);

        // Get related parties - we need full DTOs for edit view (for example to show client email)
        $client = $invoice->client;
        $supplier = $invoice->supplier;

        // Optional related DTOs (null-safe)
        // We can use DTOs from relations if loaded, otherwise try to load via Party service (with permission check)
        if (! $client && ! empty($invoice->client_id)) {
            try {
                $client = $this->partyApplicationService->findClient($userId, (int) $invoice->client_id);
            } catch (ModelNotFoundException $e) {
                $client = null;
            }
        }
        if (! $supplier && ! empty($invoice->supplier_id)) {
            try {
                $supplier = $this->partyApplicationService->findSupplier($userId, (int) $invoice->supplier_id);
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
