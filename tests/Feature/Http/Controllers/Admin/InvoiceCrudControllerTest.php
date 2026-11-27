<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\Invoice\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Status;
use App\Models\StatusCategory;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsBackendUsers;
use Tests\Support\BuildsInvoiceInput;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

final class InvoiceCrudControllerTest extends TestCase
{
    use BuildsBackendUsers;
    use BuildsInvoiceInput;
    use CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAdminTestEnvironment();
        $this->actingAs($this->adminUser, 'backpack');
    }

    #[Test]
    public function the_status_filter_offers_only_the_invoice_statuses(): void
    {
        $statusFactory = Status::factory();
        $expenseCategory = StatusCategory::where('slug', 'expense-statuses');
        $expenseStatus = $statusFactory->create(
            ['category_id' => $expenseCategory->value('id')],
        );

        $response = $this->get('/admin/invoice');

        $response->assertOk();
        foreach (InvoiceStatus::cases() as $status) {
            $response->assertSee(
                __('invoices.statuses.' . $status->value),
            );
        }
        $response->assertDontSee($expenseStatus->name);
    }

    #[Test]
    public function the_status_filter_lists_only_invoices_in_that_status(): void
    {
        $invoiceFactory = Invoice::factory();
        $invoiceFactory->create(['payment_status' => InvoiceStatus::PAID->value]);
        $invoiceFactory->create(['payment_status' => InvoiceStatus::PENDING->value]);

        $response = $this->postJson(
            '/admin/invoice/search?payment_status=' . InvoiceStatus::PAID->value,
            [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ],
        );
        $response = $response->assertOk();
        $response = $response->assertJsonPath('recordsTotal', 2);
        $response->assertJsonPath('recordsFiltered', 1);
    }

    #[Test]
    public function show_uses_the_saved_names(): void
    {
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create(
            ['supplier_name' => 'Saved supplier', 'customer_name' => 'Saved customer'],
        );
        $httpResponse = $this->get('/admin/invoice/' . $invoice->id . '/show');
        $httpResponse = $httpResponse->assertOk();
        $httpResponse = $httpResponse->assertSee('Saved supplier');
        $httpResponse->assertSee('Saved customer');
    }

    #[Test]
    public function a_draft_is_listed_without_a_status_and_the_status_filter_skips_it(): void
    {
        $invoiceFactory = Invoice::factory();
        $draftFactory = $invoiceFactory->draft();
        $draftFactory->create(['customer_name' => 'Draft customer']);
        $invoiceFactory->create(['payment_status' => InvoiceStatus::PENDING->value]);

        $response = $this->postJson(
            '/admin/invoice/search?payment_status=' . InvoiceStatus::PENDING->value,
            [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
            ],
        );

        $response = $response->assertOk();
        $response = $response->assertJsonPath('recordsTotal', 2);
        $response->assertJsonPath('recordsFiltered', 1);
    }

    #[Test]
    public function invoices_are_read_only_in_the_admin(): void
    {
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create();

        // The write operations are not registered at all
        $httpResponse = $this->get('/admin/invoice/create');
        $httpResponse->assertNotFound();
        $httpResponse = $this->post('/admin/invoice', []);
        $httpResponse->assertMethodNotAllowed();
        $httpResponse = $this->get('/admin/invoice/' . $invoice->id . '/edit');
        $httpResponse->assertNotFound();
        $httpResponse = $this->put('/admin/invoice/' . $invoice->id, []);
        $httpResponse->assertNotFound();
        $httpResponse = $this->delete('/admin/invoice/' . $invoice->id);
        $httpResponse->assertNotFound();
        $this->assertModelExists($invoice);
    }

    #[Test]
    public function guests_and_admin_users_without_permission_cannot_list_invoices(): void
    {
        $auth = auth('backpack');
        $auth->logout();
        $httpResponse = $this->get('/admin/invoice');
        $httpResponse->assertRedirect(
            route('backpack.auth.login'),
        );
        $httpClient = $this->actingAs($this->regularUser, 'backpack');
        $httpResponse = $httpClient->get('/admin/invoice');
        $httpResponse->assertForbidden();
    }
}
