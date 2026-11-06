<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Operations;

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
            'expense category' => [
                'expense-category',
                'expense_categories',
                static function (): int {
                    $expenseFactory = Expense::factory();
                    $expense = $expenseFactory->create();

                    return $expense->category_id;
                },
            ],
            'payment method' => [
                'payment-method',
                'payment_methods',
                static function (): int {
                    $invoiceFactory = Invoice::factory();
                    $invoice = $invoiceFactory->create();

                    return $invoice->payment_method_id;
                },
            ],
            'product category' => [
                'product-category',
                'product_categories',
                static function (): int {
                    $productFactory = Product::factory();
                    $product = $productFactory->create();

                    return $product->category_id;
                },
            ],
            'product' => [
                'product',
                'products',
                static function (): int {
                    $invoiceProductFactory = InvoiceProduct::factory();
                    $invoiceProduct = $invoiceProductFactory->create();

                    return $invoiceProduct->product_id;
                },
            ],
            'status' => [
                'status',
                'statuses',
                static function (): int {
                    $statusFactory = Status::factory();
                    $status = $statusFactory->create();

                    $invoiceFactory = Invoice::factory();
                    $invoice = $invoiceFactory->create(['payment_status_id' => $status->id]);

                    return $invoice->payment_status_id;
                },
            ],
            'subscription' => [
                'subscription',
                'subscriptions',
                static function (): int {
                    $paymentFactory = Payment::factory();
                    $payment = $paymentFactory->create();

                    return $payment->subscription_id;
                },
            ],
            'subscription plan' => [
                'subscription-plan',
                'subscription_plans',
                static function (): int {
                    $subscriptionFactory = Subscription::factory();
                    $subscription = $subscriptionFactory->create();

                    return $subscription->subscription_plan_id;
                },
            ],
            'tax' => [
                'tax',
                'taxes',
                static function (): int {
                    $productFactory = Product::factory();
                    $product = $productFactory->create();

                    return $product->tax_id;
                },
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

        $response = $this->deleteJson('/admin/' . $route . '/' . $id);
        $response->assertOk();
        $response->assertExactJson(
            ['error' => [__('general.messages.delete_referenced')]],
        );

        $this->assertDatabaseHas($table, ['id' => $id]);
    }
}
