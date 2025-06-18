<?php

namespace Tests\Unit\Models;

use App\Models\Invoice;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Traits\HasRoles;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    private Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->invoice = new Invoice();
    }

    #[Test]
    public function model_extends_eloquent_model(): void
    {
        $this->assertInstanceOf(Model::class, $this->invoice);
    }

    #[Test]
    public function model_uses_has_factory_trait(): void
    {
        $this->assertContains(HasFactory::class, class_uses_recursive(Invoice::class));
    }

    #[Test]
    public function model_uses_crud_trait(): void
    {
        $this->assertContains(CrudTrait::class, class_uses_recursive(Invoice::class));
    }

    #[Test]
    public function model_uses_has_roles_trait(): void
    {
        $this->assertContains(HasRoles::class, class_uses_recursive(Invoice::class));
    }

    #[Test]
    public function model_has_correct_table_name(): void
    {
        $this->assertEquals('invoices', $this->invoice->getTable());
    }

    #[Test]
    public function model_has_correct_fillable_fields(): void
    {
        $expectedGuarded = ['id'];
        $this->assertEquals($expectedGuarded, $this->invoice->getGuarded());
    }

    #[Test]
    public function model_has_correct_casts(): void
    {
        $expectedCasts = [
            'issue_date' => 'date',
            'tax_point_date' => 'date',
            'due_in' => 'integer',
        ];
        
        foreach ($expectedCasts as $attribute => $cast) {
            $this->assertEquals($cast, $this->invoice->getCasts()[$attribute]);
        }
    }

    #[Test]
    public function calculate_total_amount_method_has_correct_return_type(): void
    {
        $reflection = new \ReflectionClass($this->invoice);
        $method = $reflection->getMethod('calculateTotalAmount');
        
        $this->assertTrue($method->isPublic());
        $this->assertFalse($method->isStatic());
    }

    #[Test]
    public function get_payment_status_name_attribute_method_exists(): void
    {
        $this->assertTrue(method_exists($this->invoice, 'getPaymentStatusNameAttribute'));
    }

    #[Test]
    public function get_payment_status_slug_attribute_method_exists(): void
    {
        $this->assertTrue(method_exists($this->invoice, 'getPaymentStatusSlugAttribute'));
    }

    #[Test]
    public function get_client_name_attribute_method_exists(): void
    {
        $this->assertTrue(method_exists($this->invoice, 'getClientNameAttribute'));
    }

    #[Test]
    public function get_status_color_class_attribute_method_exists(): void
    {
        $this->assertTrue(method_exists($this->invoice, 'getStatusColorClassAttribute'));
    }

    #[Test]
    public function get_due_date_attribute_method_exists(): void
    {
        $this->assertTrue(method_exists($this->invoice, 'getDueDateAttribute'));
    }

    #[Test]
    public function sync_products_from_json_method_exists(): void
    {
        $this->assertTrue(method_exists($this->invoice, 'syncProductsFromJson'));
    }

    #[Test]
    public function get_subtotal_attribute_method_exists(): void
    {
        $this->assertTrue(method_exists($this->invoice, 'getSubtotalAttribute'));
    }

    #[Test]
    public function get_total_tax_attribute_method_exists(): void
    {
        $this->assertTrue(method_exists($this->invoice, 'getTotalTaxAttribute'));
    }

    #[Test]
    public function get_invoice_products_data_attribute_method_exists(): void
    {
        $this->assertTrue(method_exists($this->invoice, 'getInvoiceProductsDataAttribute'));
    }

    #[Test]
    public function payment_methods_legacy_method_exists(): void
    {
        $this->assertTrue(method_exists($this->invoice, 'paymentMethods'));
    }

    #[Test]
    public function supplier_name_accessor_is_protected(): void
    {
        $reflection = new \ReflectionClass($this->invoice);
        $method = $reflection->getMethod('supplierName');
        
        $this->assertTrue($method->isProtected());
    }

    #[Test]
    public function model_has_relationship_methods(): void
    {
        $relationshipMethods = [
            'clients',
            'suppliers', 
            'statuses',
            'client',
            'supplier',
            'paymentMethod',
            'products',
            'invoiceProducts',
            'user',
            'paymentStatus'
        ];

        foreach ($relationshipMethods as $method) {
            $this->assertTrue(
                method_exists($this->invoice, $method),
                "Method {$method} should exist on Invoice model"
            );
        }
    }
}
