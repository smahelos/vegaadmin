<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Operations;

use App\Models\ArtisanCommand;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Status;
use App\Models\Subscription;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\CreatesAdminTestEnvironment;

final class RestrictedDeleteOperationTest extends TestCase
{
    use CreatesAdminTestEnvironment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpAdminTestEnvironment();
        $this->actingAs($this->adminUser, 'backpack');
    }

    /**
     * Each admin CRUD with the operation, and a row that refers to one of its entries.
     *
     * @return array<string, array{string, string, Closure(): int}>
     */
    public static function referencedEntryProvider(): array
    {
        return [
            'artisan command category' => [
                'artisan-command-category',
                'artisan_command_categories',
                static fn (): int => ArtisanCommand::factory()->create()->category_id,
            ],
            'expense category' => [
                'expense-category',
                'expense_categories',
                static fn (): int => Expense::factory()->create()->category_id,
            ],
            'payment method' => [
                'payment-method',
                'payment_methods',
                static fn (): int => Invoice::factory()->create()->payment_method_id,
            ],
            'product category' => [
                'product-category',
                'product_categories',
                static fn (): int => Product::factory()->create()->category_id,
            ],
            'product' => [
                'product',
                'products',
                static fn (): int => InvoiceProduct::factory()->create()->product_id,
            ],
            'status' => [
                'status',
                'statuses',
                static function (): int {
                    $status = Status::factory()->create();

                    return Invoice::factory()->create(['payment_status_id' => $status->id])->payment_status_id;
                },
            ],
            'subscription' => [
                'subscription',
                'subscriptions',
                static fn (): int => Payment::factory()->create()->subscription_id,
            ],
            'subscription plan' => [
                'subscription-plan',
                'subscription_plans',
                static fn (): int => Subscription::factory()->create()->subscription_plan_id,
            ],
            'tax' => [
                'tax',
                'taxes',
                static fn (): int => Product::factory()->create()->tax_id,
            ],
        ];
    }

    /**
     * @param  Closure(): int  $createReferencedEntry
     */
    #[Test]
    #[DataProvider('referencedEntryProvider')]
    public function destroy_of_a_referenced_entry_answers_with_a_notification(
        string $route,
        string $table,
        Closure $createReferencedEntry,
    ): void {
        $id = $createReferencedEntry();

        $this->deleteJson('/admin/' . $route . '/' . $id)
            ->assertOk()
            ->assertExactJson(['error' => [__('general.messages.delete_referenced')]]);

        $this->assertDatabaseHas($table, ['id' => $id]);
    }
}
