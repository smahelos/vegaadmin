<?php

declare(strict_types=1);

namespace App\Application\Invoice\Services;

use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\Product\Contracts\CatalogProductReadRepositoryInterface;
use App\Domain\User\ValueObjects\UserId;

final class InvoiceProductOwnership
{
    public function __construct(private readonly CatalogProductReadRepositoryInterface $products) {}

    /** @param list<InvoiceProductWriteData> $lines */
    public function assertOwned(UserId $userId, array $lines): void
    {
        foreach ($lines as $line) {
            if ($line->productId !== null && ! $this->products->isOwnedBy($line->productId, $userId)) {
                throw new InvoiceNotFoundException('Invoice product not accessible for user');
            }
        }
    }
}
