<?php

namespace Tests\Support;

use App\Application\Invoice\DTO\InvoiceActionResult;
use App\Application\Invoice\DTO\InvoiceActionStatus;

/**
 * Helper trait for asserting unified InvoiceActionResult.
 */
trait AssertsInvoiceActionResult
{
    protected function assertInvoiceSuccess(InvoiceActionResult $result, ?string $message = null): void
    {
        $this->assertEquals(InvoiceActionStatus::SUCCESS, $result->status, ($message ?? 'Expected success').($result->error ? ' Error: '.$result->error->getMessage() : ''));
    }

    protected function assertInvoiceFailure(InvoiceActionResult $result, ?string $message = null): void
    {
        $this->assertEquals(InvoiceActionStatus::FAILURE, $result->status, ($message ?? 'Expected failure'));
    }
}
