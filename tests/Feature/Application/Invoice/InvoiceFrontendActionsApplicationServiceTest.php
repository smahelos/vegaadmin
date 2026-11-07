<?php

namespace Tests\Feature\Application\Invoice;

use App\Application\Invoice\Contracts\InvoiceFrontendActionsApplicationServiceInterface;
use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Status;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Tests\Support\AssertsInvoiceActionResult;
use Tests\TestCase;

class InvoiceFrontendActionsApplicationServiceTest extends TestCase
{
    use AssertsInvoiceActionResult;

    private InvoiceFrontendActionsApplicationServiceInterface $frontend;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $userFactory = User::factory();
        $this->user = $userFactory->create();
        Permission::firstOrCreate(
            ['name' => 'frontend.can_create_invoice', 'guard_name' => 'web'],
        );
        $this->user->givePermissionTo('frontend.can_create_invoice');
        $this->frontend = app(InvoiceFrontendActionsApplicationServiceInterface::class);
    }

    private function pendingInvoiceId(): int
    {
        $invoiceFactory = Invoice::factory();
        $statusQuery = Status::where('slug', InvoiceStatus::PENDING->value);
        $invoice = $invoiceFactory->create(
            [
                'user_id' => $this->user->id,
                'payment_status_id' => $statusQuery->firstOrFail()->id,
            ],
        );

        return $invoice->id;
    }

    #[Test]
    public function can_set_template_and_mark_paid(): void
    {
        $invoiceId = $this->pendingInvoiceId();

        $set = $this->frontend->setTemplate(
            $this->user->id,
            $invoiceId,
            'modern',
        );
        $this->assertInvoiceSuccess($set, 'Set template failed');

        $paid = $this->frontend->markAsPaid($this->user->id, $invoiceId);
        $this->assertInvoiceSuccess($paid, 'Mark paid failed');

        $statusQuery = Status::where('slug', InvoiceStatus::PAID->value);
        $this->assertDatabaseHas(
            'invoices',
            [
                'id' => $invoiceId,
                'payment_status_id' => $statusQuery->value('id'),
            ],
        );
    }
}
