<?php

namespace Tests\Feature\Infrastructure\Invoice;

use App\Application\Invoice\Contracts\InvoiceWriteRepositoryInterface;
use App\Application\Invoice\Contracts\InvoiceReadRepositoryInterface;
use App\Domain\User\ValueObjects\UserId;
use App\Infrastructure\Persistence\Eloquent\Invoice\Repositories\EloquentInvoiceRepository;
use App\Models\Invoice;
use App\Models\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EloquentInvoiceRepositoryFeatureTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceReadRepositoryInterface $read;
    private InvoiceWriteRepositoryInterface $write;

    protected function setUp(): void
    {
        parent::setUp();
    $this->read = app(InvoiceReadRepositoryInterface::class);
    $this->write = app(InvoiceWriteRepositoryInterface::class);
        Status::factory()->create(['slug' => 'paid']);
    }

    #[Test]
    public function mark_as_paid_updates_status(): void
    {
        $invoice = Invoice::factory()->create();
        $paid = Status::where('slug','paid')->first();
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
    public function find_last_invoice_for_year_returns_latest(): void
    {
    $userId = \App\Models\User::factory()->create()->id;
        $year = (int)date('Y');
        // Ensure required foreign keys exist (client, supplier, statuses, payment method created by factories)
        Invoice::factory()->create(['user_id'=>$userId,'invoice_vs'=> $year.'0001']);
        Invoice::factory()->create(['user_id'=>$userId,'invoice_vs'=> $year.'0003']);
        Invoice::factory()->create(['user_id'=>$userId,'invoice_vs'=> $year.'0002']);
    $latest = $this->read->findLastInvoiceForYear($userId, $year);
        $this->assertNotNull($latest);
        $this->assertEquals($year.'0003', $latest->invoice_vs);
    }
}
