<?php

namespace Tests\Feature\Infrastructure\Invoice;

use App\Application\Invoice\Contracts\InvoiceReadRepositoryInterface;
use App\Application\Invoice\Contracts\InvoiceWriteRepositoryInterface;
use App\Models\Invoice;
use App\Models\Status;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentInvoiceRepositoryFeatureTest extends TestCase
{
    private InvoiceReadRepositoryInterface $read;

    private InvoiceWriteRepositoryInterface $write;

    protected function setUp(): void
    {
        parent::setUp();
        $this->read = app(InvoiceReadRepositoryInterface::class);
        $this->write = app(InvoiceWriteRepositoryInterface::class);
    }

    #[Test]
    public function mark_as_paid_updates_status(): void
    {
        $invoice = Invoice::factory()->create();
        $paid = Status::where('slug', 'paid')->firstOrFail();
        $this->assertNotEquals($paid->id, $invoice->payment_status_id);
        $result = $this->write->markAsPaid($invoice->id, $paid->id);
        $this->assertTrue($result);
        $invoice->refresh();
        $this->assertEquals($paid->id, $invoice->payment_status_id);
    }

    #[Test]
    public function set_template_persists_value(): void
    {
        $invoice = Invoice::factory()->create();
        $result = $this->write->setTemplate($invoice->id, 'modern');
        $this->assertTrue($result);
        $invoice->refresh();
        $this->assertEquals('modern', $invoice->template);
    }

    #[Test]
    public function find_invoice_numbers_for_year_returns_numbers_of_that_year(): void
    {
        $userId = User::factory()->create()->id;
        $year = (int) date('Y');
        // Ensure required foreign keys exist (client, supplier, statuses, payment method created by factories)
        Invoice::factory()->create(['user_id' => $userId, 'invoice_vs' => $year . '0001']);
        Invoice::factory()->create(['user_id' => $userId, 'invoice_vs' => $year . '0003']);
        Invoice::factory()->create(['user_id' => $userId, 'invoice_vs' => $year . '0002']);
        Invoice::factory()->create(['user_id' => $userId, 'invoice_vs' => ($year - 1) . '0009']);
        $numbers = $this->read->findInvoiceNumbersForYear($userId, $year);
        sort($numbers);
        $this->assertSame([
            $year . '0001',
            $year . '0002',
            $year . '0003',
        ], $numbers);
    }
}
