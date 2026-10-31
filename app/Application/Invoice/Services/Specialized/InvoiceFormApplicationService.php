<?php

namespace App\Application\Invoice\Services\Specialized;

use App\Application\Invoice\Contracts\InvoiceFormApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceLimitApplicationServiceInterface;
use App\Application\Invoice\Contracts\InvoiceReadRepositoryInterface;
use App\Application\Invoice\DTO\InvoiceFormDTO;
use App\Application\Invoice\Form\InvoiceCreateFieldSetFactory;
use App\Application\Invoice\Services\InvoiceContactApplicationService;
use App\Application\Party\Services\ContactApplicationService;
use App\Application\Product\Contracts\InvoiceProductReadRepositoryInterface;
use App\Application\Shared\Form\DTO\FieldSetDTO;
use App\Domain\Invoice\Contracts\InvoiceDtoReadRepositoryInterface;
use App\Domain\Invoice\Contracts\InvoiceServiceInterface;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\Payment\Contracts\PaymentMethodDtoReadRepositoryInterface;
use App\Domain\Shared\Status\Contracts\StatusDtoRepositoryInterface;
use App\Domain\Shared\Tax\Contracts\TaxDtoReadRepositoryInterface;
use App\Domain\User\Contracts\UserReadRepositoryInterface;
use App\Domain\User\ValueObjects\UserId;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Service for invoice form-related operations
 */
class InvoiceFormApplicationService implements InvoiceFormApplicationServiceInterface
{
    public function __construct(
        private UserReadRepositoryInterface $userReadRepository,
        private InvoiceProductReadRepositoryInterface $productReadRepository,
        private PaymentMethodDtoReadRepositoryInterface $paymentMethodRepository,
        private TaxDtoReadRepositoryInterface $taxRepository,
        private InvoiceReadRepositoryInterface $invoiceReadRepository,
        private StatusDtoRepositoryInterface $statusRepository,
        private InvoiceLimitApplicationServiceInterface $invoiceLimitService,
        private InvoiceServiceInterface $invoiceService,
        private InvoiceDtoReadRepositoryInterface $invoices,
        private InvoiceContactApplicationService $invoiceContacts,
        private ContactApplicationService $contacts,
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
    public function prepareCreateData(int $userId): InvoiceFormDTO
    {
        // Payment methods
        $paymentMethods = $this->paymentMethodRepository->getAllForDropdown();

        // Initial empty products list
        $invoiceProducts = [];

        // Statuses
        $statuses = $this->statusRepository->getAllForDropdown();

        // Tax rates
        $taxRates = $this->taxRepository->getDphRatesForDropdown();

        // Suggested invoice number
        $suggestedNumber = $this->getNextInvoiceNumber($userId);

        // Limits data
        $limitsData = $this->getInvoicesLimit($userId);

        $fieldSet = app(InvoiceCreateFieldSetFactory::class)->build(
            $paymentMethods,
            $statuses,
        );

        return new InvoiceFormDTO($fieldSet, [
            'paymentMethods' => $paymentMethods,
            'invoiceProducts' => $invoiceProducts,
            'statuses' => $statuses,
            'taxRates' => $taxRates,
            'suggestedNumber' => $suggestedNumber,
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
            'paymentStatus',
            'paymentMethod',
        ]);

        // Dropdown and related data
        $paymentMethods = $this->paymentMethodRepository->getAllForDropdown();
        $statuses = $this->statusRepository->getAllForDropdown();
        $taxRates = $this->taxRepository->getDphRatesForDropdown();
        // Prefill existing invoice products for edit UI
        $invoiceProducts = $this->productReadRepository->allForInvoice($invoiceId);
        $limitsData = $this->getInvoicesLimit($userId);

        $fieldSet = app(InvoiceCreateFieldSetFactory::class)->build(
            $paymentMethods,
            $statuses,
        );

        return new InvoiceFormDTO($fieldSet, [
            'invoice' => $invoice,
            'paymentMethods' => $paymentMethods,
            'statuses' => $statuses,
            'taxRates' => $taxRates,
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
            'paymentMethod',
            'paymentStatus',
        ]);

        $invoiceProducts = $this->productReadRepository->allForInvoice($invoiceId);
        $limitsData = $this->getInvoicesLimit($userId);

        // The stored parties; the contact only tells whether the customer is linked to the address book
        $owner = UserId::fromInt($userId);
        $parties = $this->invoices->findByIdForUser(InvoiceId::fromInt($invoiceId), $owner)?->parties
            ?? throw new ModelNotFoundException('Invoice not accessible for user');
        $contact = $parties->contactId === null ? null : $this->contacts->get($owner, $parties->contactId);

        // For now, return a simplified DTO with empty FieldSetDTO
        $fieldSetDto = new FieldSetDTO([]);
        $meta = [
            'invoice' => $invoice,
            'limitsData' => $limitsData,
            'paymentAmount' => $invoice->payment_amount_money,
            'subtotal' => $invoice->subtotal_money,
            'totalTax' => $invoice->total_tax_money,
            'parties' => $parties,
            'contact' => $contact,
            'contactToLink' => $contact === null ? $this->invoiceContacts->findContactToLink($owner, InvoiceId::fromInt($invoiceId)) : null,
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
     * Get invoices limit (legacy compatibility)
     */
    private function getInvoicesLimit(int $userId): array
    {
        $limitInfo = $this->invoiceLimitService->getLimitInfo(new UserId($userId));

        return $limitInfo;
    }
}
