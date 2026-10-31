<?php

declare(strict_types=1);

namespace App\Application\Invoice\Services;

use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\Exceptions\InvoiceAccessDeniedException;
use App\Domain\Invoice\ValueObjects\InvoiceTotals;
use App\Domain\Product\Contracts\CatalogProductReadRepositoryInterface;
use App\Domain\Product\DTO\CatalogProductDTO;
use App\Domain\Shared\Money\Enums\Currency;
use App\Domain\User\Contracts\UserPermissionServiceInterface;
use App\Domain\User\ValueObjects\UserId;

final class InvoiceItemsApplicationService
{
    public function __construct(
        private readonly CatalogProductReadRepositoryInterface $products,
        private readonly UserPermissionServiceInterface $permissions,
        private readonly InvoiceProductOwnership $ownership,
    ) {}

    /** @return list<CatalogProductDTO> */
    public function catalog(
        UserId $actorId,
        UserId $ownerId,
        Currency $currency,
        string $search,
    ): array {
        $this->assertCanEdit($actorId);

        return $this->products->searchForUser(
            $ownerId,
            $currency,
            $search,
        );
    }

    /** @param list<InvoiceProductWriteData> $lines */
    public function preview(
        UserId $actorId,
        UserId $ownerId,
        Currency $currency,
        array $lines,
    ): InvoiceTotals {
        $this->assertCanEdit($actorId);
        $this->ownership->assertOwned($ownerId, $lines);

        return InvoiceTotals::fromProducts($currency, $lines);
    }

    private function assertCanEdit(UserId $actorId): void
    {
        $permissions = $this->permissions->getUserPermissions($actorId->toInt(), 'backpack');
        if (! in_array(
            'can_create_invoice',
            $permissions,
            true,
        ) && ! in_array(
            'can_update_invoice',
            $permissions,
            true,
        )) {
            throw new InvoiceAccessDeniedException('Invoice editing permission required');
        }
    }
}
