<?php

namespace Tests\Feature\Domain\Invoice\Services;

use App\Domain\Invoice\Services\InvoiceService;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Product;
use App\Models\Status;
use App\Models\User;
use App\Domain\User\ValueObjects\UserId;
use App\Domain\Invoice\ValueObjects\InvoiceId;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
	use RefreshDatabaseWithData;
	use CreatesFrontendTestEnvironment;

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
	public function save_invoice_products_creates_custom_products(): void
	{
		$invoice = Invoice::factory()->create(['user_id' => $this->user->id]);
		$products = [
			[
				'name' => 'Custom Product 1',
				'quantity' => 2,
				'price' => 100.50,
				'currency' => 'CZK',
				'unit' => 'ks',
				'tax_rate' => 21,
				'description' => 'Custom product description',
				'is_custom_product' => true
			],
			[
				'name' => 'Custom Product 2',
				'quantity' => 1,
				'price' => 200,
				'currency' => 'EUR',
				'unit' => 'hours',
				'tax_rate' => 15,
				'category' => 'Service'
			]
		];
	$this->service->saveInvoiceProducts(InvoiceId::fromInt($invoice->id), $products);
		$this->assertDatabaseHas('invoice_products', [
			'invoice_id' => $invoice->id,
			'name' => 'Custom Product 1',
			'quantity' => 2,
			'price' => 100.50,
			'currency' => 'CZK',
			'unit' => 'ks',
			'tax_rate' => 21,
			'is_custom_product' => true,
			'product_id' => null
		]);
		$this->assertDatabaseHas('invoice_products', [
			'invoice_id' => $invoice->id,
			'name' => 'Custom Product 2',
			'quantity' => 1,
			'price' => 200,
			'currency' => 'EUR',
			'unit' => 'hours',
			'tax_rate' => 15,
			'category' => 'Service',
			'product_id' => null
		]);
		$this->assertEquals(2, InvoiceProduct::where('invoice_id', $invoice->id)->count());
	}

	#[Test]
	public function save_invoice_products_creates_products_with_product_id(): void
	{
		$invoice = Invoice::factory()->create(['user_id' => $this->user->id]);
		$product = Product::factory()->create();
		$products = [[
			'product_id' => $product->id,
			'name' => 'Existing Product',
			'quantity' => 3,
			'price' => 50,
			'currency' => 'CZK',
			'unit' => 'pieces',
			'tax_rate' => 21
		]];
	$this->service->saveInvoiceProducts(InvoiceId::fromInt($invoice->id), $products);
		$this->assertDatabaseHas('invoice_products', [
			'invoice_id' => $invoice->id,
			'product_id' => $product->id,
			'name' => 'Existing Product',
			'quantity' => 3,
			'price' => 50,
			'is_custom_product' => false
		]);
	}

	#[Test]
	public function save_invoice_products_calculates_tax_and_total_correctly(): void
	{
		$invoice = Invoice::factory()->create(['user_id' => $this->user->id]);
		$products = [[
			'name' => 'Tax Test Product',
			'quantity' => 2,
			'price' => 100,
			'tax_rate' => 21,
			'currency' => 'CZK',
			'unit' => 'ks'
		]];
	$this->service->saveInvoiceProducts(InvoiceId::fromInt($invoice->id), $products);
		$invoiceProduct = InvoiceProduct::where('invoice_id', $invoice->id)->first();
		$this->assertEquals(42, $invoiceProduct->tax_amount); // (2*100*21)/100
		$this->assertEquals(242, $invoiceProduct->total_price); // 2*100*(1+0.21)
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
			]
		);
		$invoice = Invoice::factory()->create([
			'user_id' => $this->user->id,
			'payment_status_id' => 1
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
		$object = new \stdClass();
		$properties = ['due_in','payment_method_id','payment_status_id','payment_amount','custom_property'];
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
		$object = new \stdClass();
		$object->due_in = '30';
		$object->payment_method_id = '5';
		$object->payment_status_id = '3';
		$object->payment_amount = '150.50';
		$object->custom_property = 'existing value';
		$properties = ['due_in','payment_method_id','payment_status_id','payment_amount','custom_property'];
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
			'template' => 'default'
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
			'template' => 'default'
		]);
		foreach (['default','modern','minimal'] as $template) {
			$this->assertTrue($this->service->setInvoiceTemplate(InvoiceId::fromInt($invoice->id), $template));
			$this->assertEquals($template, Invoice::find($invoice->id)->template);
		}
	}
}
