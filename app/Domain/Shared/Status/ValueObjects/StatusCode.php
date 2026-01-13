<?php

namespace App\Domain\Shared\Status\ValueObjects;

/**
 * Enum of allowed status slugs synchronized with `statuses.slug` records.
 * IMPORTANT: Keep in sync with database seed/migrations.
 */
enum StatusCode: string
{
    case APPROVED = 'approved';
    case CANCELLED = 'cancelled';
    case CANCELLED_EXPENSE = 'cancelled-expense';
    case CANCELLED_STATUS = 'cancelled-status';
    case DRAFT = 'draft';
    case IN_REVIEW = 'in-review';
    case OVERDUE = 'overdue';
    case PAID = 'paid';
    case PAID_EXPENSE = 'paid-expense';
    case PAID_STATUS = 'paid-status';
    case PARTIALLY_PAID = 'partially-paid';
    case PENDING = 'pending';
    case PENDING_PAYMENT = 'pending-payment';
    case REJECTED = 'rejected';
    case UNPAID = 'unpaid'; // added

    /**
     * Human readable default English label (translations handled in lang files later).
     */
    public function label(): string
    {
        return match($this) {
            self::APPROVED => 'Approved',
            self::CANCELLED => 'Cancelled',
            self::CANCELLED_EXPENSE => 'Cancelled Expense',
            self::CANCELLED_STATUS => 'Cancelled Status',
            self::DRAFT => 'Draft',
            self::IN_REVIEW => 'In Review',
            self::OVERDUE => 'Overdue',
            self::PAID => 'Paid',
            self::PAID_EXPENSE => 'Paid Expense',
            self::PAID_STATUS => 'Paid Status',
            self::PARTIALLY_PAID => 'Partially Paid',
            self::PENDING => 'Pending',
            self::PENDING_PAYMENT => 'Pending Payment',
            self::REJECTED => 'Rejected',
            self::UNPAID => 'Unpaid', // added
        };
    }

    /**
     * @return string[] List of all enum slugs.
     */
    public static function allSlugs(): array
    {
        return array_map(static fn(self $c) => $c->value, self::cases());
    }

    /**
     * Try resolve from slug.
     */
    public static function tryFromSlug(string $slug): ?self
    {
        return self::tryFrom($slug);
    }
}
