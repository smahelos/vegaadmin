<?php

namespace Tests\Unit\Models;

use App\Models\InvoiceProduct;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceProductTest extends TestCase
{
    private InvoiceProduct $model;

    protected function setUp(): void
    {
        parent::setUp();
        $this->model = new InvoiceProduct();
    }

    #[Test]
    public function model_extends_eloquent_model(): void
    {
        $this->assertInstanceOf(Model::class, $this->model);
    }

    #[Test]
    public function model_uses_has_factory_trait(): void
    {
        $this->assertArrayHasKey(HasFactory::class, class_uses($this->model));
    }

    #[Test]
    public function table_name_is_invoice_products(): void
    {
        $this->assertEquals('invoice_products', $this->model->getTable());
    }

    #[Test]
    public function fillable_attributes_are_properly_defined(): void
    {
        $expectedFillable = [
            'invoice_id',
            'product_id',
            'name',
            'quantity',
            'price',
            'currency',
            'unit',
            'category',
            'description',
            'is_custom_product',
            'tax_rate',
            'tax_amount',
            'total_price',
        ];

        $this->assertEquals($expectedFillable, $this->model->getFillable());
    }

    #[Test]
    public function casts_are_properly_defined(): void
    {
        $expectedCasts = [
            'quantity' => 'float',
            'price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'total_price' => 'decimal:2',
            'is_custom_product' => 'boolean',
        ];

        foreach ($expectedCasts as $attribute => $expectedCast) {
            $this->assertEquals($expectedCast, $this->model->getCasts()[$attribute]);
        }
    }

    #[Test]
    public function invoice_relationship_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'invoice'));
    }

    #[Test]
    public function product_relationship_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'product'));
    }

    #[Test]
    public function calculate_tax_amount_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'calculateTaxAmount'));
    }

    #[Test]
    public function calculate_total_price_method_exists(): void
    {
        $this->assertTrue(method_exists($this->model, 'calculateTotalPrice'));
    }

    #[Test]
    public function calculate_tax_amount_works_correctly(): void
    {
        $this->model->price = 100.00;
        $this->model->quantity = 2;
        $this->model->tax_rate = 21.00;

        $result = $this->model->calculateTaxAmount();

        $this->assertEquals(42.00, $result);
        $this->assertEquals(42.00, $this->model->tax_amount);
    }

    #[Test]
    public function calculate_total_price_works_correctly(): void
    {
        $this->model->price = 100.00;
        $this->model->quantity = 2;
        $this->model->tax_amount = 42.00;

        $result = $this->model->calculateTotalPrice();

        $this->assertEquals(242.00, $result);
        $this->assertEquals(242.00, $this->model->total_price);
    }

    #[Test]
    public function calculate_tax_amount_handles_zero_values(): void
    {
        $this->model->price = 0;
        $this->model->quantity = 5;
        $this->model->tax_rate = 21.00;

        $result = $this->model->calculateTaxAmount();

        $this->assertEquals(0, $result);
        $this->assertEquals(0, $this->model->tax_amount);
    }

    #[Test]
    public function calculate_total_price_handles_zero_tax(): void
    {
        $this->model->price = 50.00;
        $this->model->quantity = 3;
        $this->model->tax_amount = 0;

        $result = $this->model->calculateTotalPrice();

        $this->assertEquals(150.00, $result);
        $this->assertEquals(150.00, $this->model->total_price);
    }

    #[Test]
    public function boot_method_is_registered(): void
    {
        // Check that the boot method exists (protected static)
        $reflection = new \ReflectionClass($this->model);
        $this->assertTrue($reflection->hasMethod('boot'));
        
        $bootMethod = $reflection->getMethod('boot');
        $this->assertTrue($bootMethod->isStatic());
        $this->assertTrue($bootMethod->isProtected());
    }
}
