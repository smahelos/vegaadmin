<?php

declare(strict_types=1);

namespace App\Application\Invoice\Queries;

use App\Application\Shared\UseCase\Access;
use App\Application\Shared\UseCase\Contracts\QueryInterface;
use App\Application\Shared\UseCase\OwnedUseCase;
use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\User\Enums\Permission;
use App\Domain\User\ValueObjects\UserId;

/**
 * The totals of edited invoice lines before they are saved.
 *
 * @implements QueryInterface<InvoiceTotals>
 */
final readonly class PreviewInvoiceTotals extends OwnedUseCase implements QueryInterface
{
    /**
     * @param  list<InvoiceProductWriteData>  $lines
     */
    public function __construct(
        UserId $owner,
        public Currency $currency,
        public array $lines,
    ) {
        parent::__construct($owner);
    }

    public static function access(): Access
    {
        return Access::anyOf(
            Permission::INVOICE_CREATE,
            Permission::INVOICE_UPDATE,
        );
    }
}
