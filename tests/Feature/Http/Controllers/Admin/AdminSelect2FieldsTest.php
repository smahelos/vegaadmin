<?php

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Supplier;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

/**
 * Billex's select2-based fields (select2, select2_from_array, select2_from_ajax, relationship)
 * render a select initialized by the shared select2 initializers.
 */
class AdminSelect2FieldsTest extends TestCase
{
    use CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAdminTestEnvironment();
    }

    #[Test]
    public function relationship_and_select2_from_array_fields_use_select2(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $content = $this->get('/admin/expense/create')->assertOk()->getContent();

        $this->assertSelect2Field(
            $content,
            'supplier_id',
            'bpFieldInitBillexSelect2',
        );
        $this->assertSelect2Field(
            $content,
            'payment_method_id',
            'bpFieldInitBillexSelect2',
        );
        $this->assertSelect2Field(
            $content,
            'currency',
            'bpFieldInitBillexSelect2',
        );
        $this->assertStringContainsString('packages/select2/dist/js/select2.full.min.js', $content);
    }

    #[Test]
    public function relationship_field_without_relation_method_renders(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $content = $this->get('/admin/invoice/create')->assertOk()->getContent();

        // payment_status_id has no matching relation method name; the column itself is the value
        $this->assertSelect2Field(
            $content,
            'payment_status_id',
            'bpFieldInitBillexSelect2',
        );
    }

    #[Test]
    public function select2_and_select2_from_ajax_fields_use_select2(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $content = $this->get('/admin/subscription/create')->assertOk()->getContent();

        $this->assertSelect2Field(
            $content,
            'subscription_plan_id',
            'bpFieldInitBillexSelect2',
        );
        $this->assertSelect2Field(
            $content,
            'user_id',
            'bpFieldInitBillexSelect2Ajax',
        );
        $this->assertStringContainsString('data-source="' . url('api/admin/user/search') . '"', $content);
        $this->assertStringContainsString('data-minimum-input-length="2"', $content);
    }

    #[Test]
    public function edit_form_preselects_the_related_record(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $supplier = Supplier::factory()->create(['name' => 'Supplier ' . uniqid()]);
        $expense = Expense::factory()->create(['supplier_id' => $supplier->id]);

        $response = $this->get("/admin/expense/{$expense->id}/edit");

        $response->assertOk();
        $response->assertSee('<option value="' . $supplier->id . '" selected>' . $supplier->name . '</option>', false);
    }

    #[Test]
    public function invoice_date_and_payment_fields_keep_their_types(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $content = $this->get('/admin/invoice/create')->assertOk()->getContent();

        $this->assertStringContainsString('name="issue_date" value="' . date('Y-m-d') . '" data-datepicker-value', $content);
        $this->assertStringContainsString('name="tax_point_date" value="' . date('Y-m-d') . '" data-datepicker-value', $content);
        $this->assertSelect2Field(
            $content,
            'payment_method_id',
            'bpFieldInitBillexSelect2',
        );
        $this->assertSelect2Field(
            $content,
            'due_in',
            'bpFieldInitBillexSelect2',
        );
        $this->assertSelect2Field(
            $content,
            'payment_currency',
            'bpFieldInitBillexSelect2',
        );
    }

    #[Test]
    public function invoice_edit_form_keeps_stored_payment_values(): void
    {
        $this->actingAs($this->adminUser, 'backpack');
        $invoice = Invoice::factory()->create([
            'user_id' => $this->adminUser->id,
            'tax_point_date' => null,
            'due_in' => 21,
        ]);

        $content = $this->get("/admin/invoice/{$invoice->id}/edit")->assertOk()->getContent();

        $this->assertStringContainsString('name="tax_point_date" value="" data-datepicker-value', $content);
        $this->assertMatchesRegularExpression('/<option value="21"\s+selected/', $content);
    }

    #[Test]
    public function expense_form_has_one_payment_method_field(): void
    {
        $this->actingAs($this->adminUser, 'backpack');

        $content = $this->get('/admin/expense/create')->assertOk()->getContent();

        $this->assertSame(1, preg_match_all('/\sname="(payment_method_id|paymentMethod)"/', $content));
    }

    private function assertSelect2Field(
        string $content,
        string $name,
        string $initFunction,
    ): void {
        $this->assertMatchesRegularExpression(
            '/<select[^>]*name="' . preg_quote($name, '/') . '"[^>]*data-init-function="' . $initFunction . '"/s',
            $content,
            "Field {$name} is not a select initialized by {$initFunction}",
        );
    }
}
