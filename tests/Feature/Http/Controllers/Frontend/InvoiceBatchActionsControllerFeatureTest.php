<?php

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Domain\User\Enums\Permission;
use App\Models\Invoice;
use App\Models\Permission as PermissionModel;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceBatchActionsControllerFeatureTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $userFactory = User::factory();
        $this->user = $userFactory->create();
        $this->user->givePermissionTo(
            PermissionModel::findByName(Permission::INVOICE_UPDATE->value, 'web'),
        );
    }

    #[Test]
    public function batch_actions_template_and_mark_paid_success(): void
    {
        $this->actingAs($this->user);
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(['user_id' => $this->user->id]);
        $application = app();
        $response = $this->post(
            route(
                'frontend.invoice.batch-actions',
                ['locale' => $application->getLocale(), 'id' => $invoice->id],
            ),
            [
                'template' => 'modern',
                'mark_paid' => true,
            ],
        );
        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    #[Test]
    public function batch_actions_rejects_invalid_template(): void
    {
        $this->actingAs($this->user);
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(['user_id' => $this->user->id]);
        $application = app();
        $response = $this->post(
            route(
                'frontend.invoice.batch-actions',
                ['locale' => $application->getLocale(), 'id' => $invoice->id],
            ),
            [
                'template' => 'invalid-template',
            ],
        );
        $response->assertSessionHasErrors('template');
    }

    #[Test]
    public function batch_actions_rejects_unknown_status(): void
    {
        $this->actingAs($this->user);
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(['user_id' => $this->user->id]);
        $application = app();
        $response = $this->post(
            route(
                'frontend.invoice.batch-actions',
                ['locale' => $application->getLocale(), 'id' => $invoice->id],
            ),
            [
                'status' => 'overdue',
            ],
        );
        $response->assertSessionHasErrors('status');
    }
}
