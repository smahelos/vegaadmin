<?php

namespace Tests\Feature\Domain\Invoice\Services;

use App\Domain\Invoice\DTO\InvoiceProductWriteData;
use App\Domain\Invoice\Services\InvoiceService;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use App\Domain\Product\ValueObjects\ProductId;
use App\Domain\Shared\Money\ValueObjects\Money;
use App\Domain\Shared\Unit\Enums\Unit;
use App\Domain\User\ValueObjects\UserId;
use App\Models\Invoice;
use App\Models\Product;
use App\Models\Status;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesFrontendTestEnvironment;
use Tests\Traits\RefreshDatabaseWithData;

/**
 * Consolidated feature tests for InvoiceService.
 * Original file renamed to match *FeatureTest naming convention.
 */
class InvoiceServiceFeatureTest extends TestCase
{
    use CreatesFrontendTestEnvironment;
    use RefreshDatabaseWithData;

    private InvoiceService $service;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFrontendTestEnvironment();
        // Resolve through container (constructor now expects repository dependencies)
        $this->service = app(InvoiceService::class);
    }

    // --- Save products ---

    #[Test]
    public function save_invoice_products_stores_each_line_with_its_computed_amounts(): void
    {
        $invoice = Invoice::factory()->create(['user_id' => $this->user->id]);
        $product = Product::factory()->create(['user_id' => $this->user->id]);

        $this->service->saveInvoiceProducts(InvoiceId::fromInt($invoice->id), [
            new InvoiceProductWriteData(
                name: 'Consulting',
                quantity: '1.5',
                unit: Unit::HOURS,
                price: Money::fromString('249.30', 'CZK'),
                taxRate: '12',
                category: 'Service',
            ),
            new InvoiceProductWriteData(
                name: 'Existing Product',
                quantity: '3',
                unit: Unit::PIECES,
                price: Money::fromString('74.67', 'CZK'),
                taxRate: '21',
                productId: ProductId::fromInt($product->id),
            ),
        ]);

        $this->assertDatabaseHas('invoice_products', [
            'invoice_id' => $invoice->id,
            'name' => 'Consulting',
            'product_id' => null,
            'is_custom_product' => true,
            'quantity' => '1.500',
            'unit' => 'hours',
            'price' => '249.30',
            'tax_rate' => '12.00',
            'tax_amount' => '44.87',
            'total_price' => '418.82',
            'category' => 'Service',
        ]);
        $this->assertDatabaseHas('invoice_products', [
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'is_custom_product' => false,
            'tax_amount' => '47.04',
            'total_price' => '271.05',
        ]);
    }

    // --- Mark as paid ---

    #[Test]
    public function mark_invoice_as_paid_updates_status(): void
    {
        Auth::login($this->user);
        // Ensure a single 'paid' status exists (avoid unique constraint violation if seeded)
        $paidStatus = Status::firstOrCreate(
            ['slug' => 'paid'],
            [
                'name' => 'Paid',
                'category_id' => 1,
                'color' => 'bg-green-100 text-green-800',
                'description' => 'Autocreated paid status for tests',
                'is_active' => true,
            ],
        );
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'payment_status_id' => 1,
        ]);
        $this->assertTrue($this->service->markInvoiceAsPaid(InvoiceId::fromInt($invoice->id)));
        $invoice->refresh();
        $this->assertEquals($paidStatus->id, $invoice->payment_status_id);
    }

    #[Test]
    public function mark_invoice_as_paid_returns_false_when_no_paid_status_exists(): void
    {
        Auth::login($this->user);
        // Remove any existing 'paid' status to simulate missing status scenario
        Status::where('slug', 'paid')->delete();
        $invoice = Invoice::factory()->create(['user_id' => $this->user->id]);
        $this->assertFalse($this->service->markInvoiceAsPaid(InvoiceId::fromInt($invoice->id)));
    }

    #[Test]
    public function mark_invoice_as_paid_returns_false_for_nonexistent_invoice(): void
    {
        Auth::login($this->user);
        $this->assertFalse($this->service->markInvoiceAsPaid(InvoiceId::fromInt(99999)));
    }

    // --- Ensure object properties ---

    #[Test]
    public function ensure_object_properties_sets_default_values(): void
    {
        $object = new \stdClass;
        $properties = [
            'due_in',
            'payment_method_id',
            'payment_status_id',
            'payment_amount',
            'custom_property',
        ];
        $this->service->ensureObjectProperties($object, $properties);
        $this->assertEquals(14, $object->due_in);
        $this->assertEquals(1, $object->payment_method_id);
        $this->assertEquals(1, $object->payment_status_id);
        $this->assertEquals(0, $object->payment_amount);
        $this->assertEquals('', $object->custom_property);
    }

    #[Test]
    public function ensure_object_properties_converts_existing_values(): void
    {
        $object = new \stdClass;
        $object->due_in = '30';
        $object->payment_method_id = '5';
        $object->payment_status_id = '3';
        $object->payment_amount = '150.50';
        $object->custom_property = 'existing value';
        $properties = [
            'due_in',
            'payment_method_id',
            'payment_status_id',
            'payment_amount',
            'custom_property',
        ];
        $this->service->ensureObjectProperties($object, $properties);
        $this->assertSame(30, $object->due_in);
        $this->assertSame(5, $object->payment_method_id);
        $this->assertSame(3, $object->payment_status_id);
        $this->assertSame(150.5, $object->payment_amount);
        $this->assertEquals('existing value', $object->custom_property);
    }

    // --- Template handling ---

    #[Test]
    public function set_invoice_template_updates_invoice_successfully(): void
    {
        Auth::login($this->user);
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'template' => 'default',
        ]);
        $this->assertTrue($this->service->setInvoiceTemplate(InvoiceId::fromInt($invoice->id), 'modern'));
        $this->assertEquals('modern', Invoice::find($invoice->id)->template);
    }

    #[Test]
    public function set_invoice_template_fails_for_non_existent_invoice(): void
    {
        Auth::login($this->user);
        $this->assertFalse($this->service->setInvoiceTemplate(InvoiceId::fromInt(99999), 'modern'));
    }

    #[Test]
    public function set_invoice_template_other_users_invoice_authorization_out_of_scope(): void
    {
        // Ownership is enforced at application layer (e.g. controller / policy), not inside domain service.
        $this->markTestIncomplete('Ownership check belongs to application layer; domain service intentionally allows update.');
    }

    #[Test]
    public function set_invoice_template_accepts_valid_template_names(): void
    {
        Auth::login($this->user);
        $invoice = Invoice::factory()->create([
            'user_id' => $this->user->id,
            'template' => 'default',
        ]);
        foreach ([
            'default',
            'modern',
            'minimal',
        ] as $template) {
            $this->assertTrue($this->service->setInvoiceTemplate(InvoiceId::fromInt($invoice->id), $template));
            $this->assertEquals($template, Invoice::find($invoice->id)->template);
        }
    }

    // --- Invoice number generation ---

    #[Test]
    public function generate_invoice_number_starts_sequence_for_user_without_invoices(): void
    {
        $year = (int) date('Y');
        $user = User::factory()->create();

        $this->assertSame($year . '0001', $this->service->generateInvoiceNumber(UserId::fromInt($user->id), $year));
    }

    #[Test]
    public function generate_invoice_number_continues_sequence_of_current_year(): void
    {
        $year = (int) date('Y');
        $user = User::factory()->create();
        Invoice::factory()->create(['user_id' => $user->id, 'invoice_vs' => $year . '0007']);
        Invoice::factory()->create(['user_id' => $user->id, 'invoice_vs' => $year . '0012']);

        $this->assertSame($year . '0013', $this->service->generateInvoiceNumber(UserId::fromInt($user->id), $year));
    }

    #[Test]
    public function generate_invoice_number_restarts_sequence_after_non_standard_last_number(): void
    {
        $year = (int) date('Y');
        $user = User::factory()->create();
        Invoice::factory()->create(['user_id' => $user->id, 'invoice_vs' => $year . '-A1']);

        $this->assertSame($year . '0001', $this->service->generateInvoiceNumber(UserId::fromInt($user->id), $year));
    }

    #[Test]
    public function generate_invoice_number_continues_past_9999(): void
    {
        $year = (int) date('Y');
        $user = User::factory()->create();
        Invoice::factory()->create(['user_id' => $user->id, 'invoice_vs' => $year . '9999']);
        Invoice::factory()->create(['user_id' => $user->id, 'invoice_vs' => $year . '10000']);

        $this->assertSame($year . '10001', $this->service->generateInvoiceNumber(UserId::fromInt($user->id), $year));
    }

    #[Test]
    public function generate_invoice_number_ignores_non_standard_numbers(): void
    {
        $year = (int) date('Y');
        $user = User::factory()->create();
        Invoice::factory()->create(['user_id' => $user->id, 'invoice_vs' => $year . '0005']);
        Invoice::factory()->create(['user_id' => $user->id, 'invoice_vs' => $year . '-ZZZ-LONG-NUMBER']);

        $this->assertSame($year . '0006', $this->service->generateInvoiceNumber(UserId::fromInt($user->id), $year));
    }
}
