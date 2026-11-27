<?php

declare(strict_types=1);

namespace App\Application\Invoice\Commands;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\CommandInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Invoice\DTO\InvoiceDTO;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Invoice\ValueObjects\InvoiceNumber;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;
use DateTimeImmutable;

/**
 * Only the owner issues a document (D-050): the admin shows invoices read-only.
 *
 * @implements CommandInterface<InvoiceDTO>
 */
final readonly class IssueInvoice extends OwnedUseCase implements CommandInterface
{
    /**
     * @param  DateTimeImmutable|null  $taxPointDate  Null for the issue date
     */
    public function __construct(
        UserId $owner,
        public InvoiceId $id,
        public InvoiceNumber $number,
        public DateTimeImmutable $issueDate,
        public ?DateTimeImmutable $taxPointDate,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::permission(Permission::INVOICE_UPDATE);
    }
}
