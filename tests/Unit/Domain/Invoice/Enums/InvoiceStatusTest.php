<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Invoice\Enums;

use App\Domain\Invoice\Enums\InvoiceStatus;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class InvoiceStatusTest extends TestCase
{
    #[Test]
    public function pending_and_partially_paid_invoices_are_unpaid(): void
    {
        $this->assertSame([InvoiceStatus::PENDING, InvoiceStatus::PARTIALLY_PAID], InvoiceStatus::unpaid());
    }
}
