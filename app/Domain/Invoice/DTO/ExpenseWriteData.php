<?php

declare(strict_types=1);

namespace App\Domain\Invoice\DTO;

use App\Domain\Party\DTO\DocumentAccountDTO;
use App\Domain\Party\DTO\DocumentPartyDTO;
use App\Domain\Party\ValueObjects\BankAccountId;
use App\Domain\Party\ValueObjects\BusinessId;
use App\Domain\Party\ValueObjects\ContactId;
use App\Domain\Shared\File\DTO\IncomingFile;
use App\Domain\Shared\Money\ValueObjects\Money;
use DateTimeImmutable;

final readonly class ExpenseWriteData
{
    /**
     * @param  list<IncomingFile>  $uploads
     * @param  list<string>  $removedAttachments
     */
    public function __construct(
        public BusinessId $businessId,
        public ?ContactId $contactId,
        public ?BankAccountId $bankAccountId,
        public DocumentPartyDTO $supplier,
        public ?DocumentAccountDTO $account,
        public DateTimeImmutable $expenseDate,
        public Money $amount,
        public int $categoryId,
        public ?int $paymentMethodId,
        public ?string $referenceNumber,
        public ?string $description,
        public Money $taxAmount,
        public bool $taxIncluded,
        public ?int $statusId,
        public array $uploads = [],
        public array $removedAttachments = [],
        public ?IncomingFile $supplierLogo = null,
        public bool $removeSupplierLogo = false,
    ) {}
}
