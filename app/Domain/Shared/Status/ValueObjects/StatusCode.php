<?php

namespace App\Domain\Shared\Status\ValueObjects;

/**
 * Enum of allowed status slugs synchronized with `statuses.slug` records.
 * IMPORTANT: Keep in sync with the statuses in ReferenceDataSeeder.
 */
enum StatusCode: string
{
    case APPROVED = 'approved';
    case CANCELLED = 'cancelled';
    case CANCELLED_EXPENSE = 'cancelled-expense';
    case DRAFT = 'draft';
    case IN_REVIEW = 'in-review';
    case OVERDUE = 'overdue';
    case PAID = 'paid';
    case PAID_EXPENSE = 'paid-expense';
    case PARTIALLY_PAID = 'partially-paid';
    case PENDING = 'pending';
    case REJECTED = 'rejected';

    /**
     * Human readable default English label (translations handled in lang files later).
     */
    public function label(): string
    {
        return match ($this) {
            self::APPROVED => 'Approved',
            self::CANCELLED => 'Cancelled',
            self::CANCELLED_EXPENSE => 'Cancelled Expense',
            self::DRAFT => 'Draft',
            self::IN_REVIEW => 'In Review',
            self::OVERDUE => 'Overdue',
            self::PAID => 'Paid',
            self::PAID_EXPENSE => 'Paid Expense',
            self::PARTIALLY_PAID => 'Partially Paid',
            self::PENDING => 'Pending',
            self::REJECTED => 'Rejected',
        };
    }

    /**
     * @return string[] List of all enum slugs.
     */
    public static function allSlugs(): array
    {
        return array_map(static fn (self $c) => $c->value, self::cases());
    }

    /**
     * Try resolve from slug.
     */
    public static function tryFromSlug(string $slug): ?self
    {
        return self::tryFrom($slug);
    }
}
