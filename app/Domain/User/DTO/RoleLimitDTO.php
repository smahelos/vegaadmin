<?php

declare(strict_types=1);

namespace App\Domain\User\DTO;

use App\Domain\User\Enums\LimitedEntity;

/**
 * The limits of one plan role: null is unlimited, 0 not allowed.
 */
final readonly class RoleLimitDTO
{
    public function __construct(
        public string $role,
        public ?int $invoices,
        public ?int $expenses,
        public ?int $businesses,
        public ?int $contacts,
        public ?int $products,
    ) {}

    public function limit(LimitedEntity $entity): ?int
    {
        return match ($entity) {
            LimitedEntity::INVOICE => $this->invoices,
            LimitedEntity::EXPENSE => $this->expenses,
            LimitedEntity::BUSINESS => $this->businesses,
            LimitedEntity::CONTACT => $this->contacts,
            LimitedEntity::PRODUCT => $this->products,
        };
    }
}
