<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Migrations;

use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Status;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionPlanFeature;
use App\Models\Tax;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\AssertsDatabaseRefusal;
use Tests\TestCase;

/**
 * A document never disappears or changes because something else is deleted: the foreign keys
 * of invoices, their lines, expenses and Billex's own payments restrict, and only the parts a
 * record owns are deleted with it.
 */
final class ReferentialIntegrityTest extends TestCase
{
    use AssertsDatabaseRefusal;

    #[Test]
    public function an_invoice_keeps_its_business_contact_account_payment_method_status_and_owner(): void
    {
        $invoiceFactory = Invoice::factory();
        $invoice = $invoiceFactory->create();
        $business = Business::findOrFail($invoice->business_id);
        $bankAccountFactory = BankAccount::factory();
        $bankAccountFactory = $bankAccountFactory->ownedBy($business);
        $account = $bankAccountFactory->create();
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $invoice->user_id]);
        $invoice->forceFill(
            ['contact_id' => $contact->id, 'bank_account_id' => $account->id],
        )->save();

        $this->assertDeleteRefused($business);
        $this->assertDeleteRefused($contact);
        $this->assertDeleteRefused($account);
        $this->assertDeleteRefused($invoice->paymentMethod);
        $this->assertDeleteRefused(
            Status::findOrFail($invoice->payment_status_id),
        );
        $this->assertForceDeleteRefused(
            User::findOrFail($invoice->user_id),
        );

        $this->assertDatabaseHas(
            'invoices',
            [
                'id' => $invoice->id,
                'business_id' => $business->id,
                'contact_id' => $contact->id,
                'bank_account_id' => $account->id,
                'payment_method_id' => $invoice->payment_method_id,
                'payment_status_id' => $invoice->payment_status_id,
                'user_id' => $invoice->user_id,
            ],
        );
    }

    #[Test]
    public function an_invoice_line_keeps_its_product(): void
    {
        $invoiceProductFactory = InvoiceProduct::factory();
        $line = $invoiceProductFactory->create();

        $this->assertDeleteRefused(
            Product::findOrFail($line->product_id),
        );
        $this->assertDatabaseHas('invoice_products', ['id' => $line->id, 'product_id' => $line->product_id]);
    }

    #[Test]
    public function the_same_product_can_be_on_several_lines_of_one_invoice(): void
    {
        $invoiceProductFactory = InvoiceProduct::factory();
        $line = $invoiceProductFactory->create();

        $invoiceProductFactory2 = InvoiceProduct::factory();
        $invoiceProductFactory2->create(
            [
                'invoice_id' => $line->invoice_id,
                'product_id' => $line->product_id,
            ],
        );

        $this->assertSame(
            2,
            InvoiceProduct::where('invoice_id', $line->invoice_id)->count(),
        );
    }

    #[Test]
    public function deleting_an_invoice_deletes_its_lines(): void
    {
        $invoiceProductFactory = InvoiceProduct::factory();
        $line = $invoiceProductFactory->create();

        Invoice::findOrFail($line->invoice_id)->delete();

        $this->assertDatabaseMissing('invoice_products', ['id' => $line->id]);
    }

    #[Test]
    public function a_product_keeps_its_tax_rate_and_category(): void
    {
        $productFactory = Product::factory();
        $product = $productFactory->create();

        $this->assertDeleteRefused(
            Tax::findOrFail($product->tax_id),
        );
        $this->assertDeleteRefused($product->category);
    }

    #[Test]
    public function an_expense_keeps_its_business_contact_account_category_payment_method_and_status(): void
    {
        $expenseFactory = Expense::factory();
        $expense = $expenseFactory->create(['status_id' => Status::factory()]);
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $expense->user_id]);
        $bankAccountFactory = BankAccount::factory();
        $bankAccountFactory = $bankAccountFactory->ownedBy($contact);
        $account = $bankAccountFactory->create();
        $expense->forceFill(
            ['contact_id' => $contact->id, 'bank_account_id' => $account->id],
        )->save();

        $this->assertDeleteRefused(
            Business::findOrFail($expense->business_id),
        );
        $this->assertDeleteRefused($contact);
        $this->assertDeleteRefused($account);
        $this->assertDeleteRefused($expense->category);
        $this->assertDeleteRefused($expense->paymentMethod);
        $this->assertDeleteRefused(
            Status::findOrFail($expense->status_id),
        );

        $expense->delete();
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    #[Test]
    public function billex_payments_keep_their_subscription_plan_and_user(): void
    {
        $paymentFactory = Payment::factory();
        $payment = $paymentFactory->create();
        $subscription = $payment->subscription;

        $this->assertDeleteRefused($subscription);
        $this->assertDeleteRefused(
            SubscriptionPlan::findOrFail($subscription->subscription_plan_id),
        );
        $this->assertForceDeleteRefused(
            User::findOrFail($subscription->user_id),
        );
    }

    #[Test]
    public function deleting_a_plan_feature_removes_it_from_plans(): void
    {
        $subscriptionPlanFactory = SubscriptionPlan::factory();
        $plan = $subscriptionPlanFactory->create();
        $subscriptionPlanFeatureFactory = SubscriptionPlanFeature::factory();
        $feature = $subscriptionPlanFeatureFactory->create();
        DB::table('subscription_plan_subscription_plan_feature')->insert(
            [
                'subscription_plan_id' => $plan->id,
                'subscription_plan_feature_id' => $feature->id,
            ],
        );

        $feature->forceDelete();

        $this->assertDatabaseMissing('subscription_plan_subscription_plan_feature', ['subscription_plan_id' => $plan->id]);
    }

    #[Test]
    public function product_slugs_are_unique_per_user(): void
    {
        $productFactory = Product::factory();
        $product = $productFactory->create();
        $userFactory = User::factory();
        $otherUser = $userFactory->create();

        $productFactory2 = Product::factory();
        $productFactory2->create(['user_id' => $otherUser->id, 'slug' => $product->slug]);

        $this->assertRefused(
            fn () => Product::factory()->create(['user_id' => $product->user_id, 'slug' => $product->slug]),
            1062,
        );
    }

    private function assertDeleteRefused(Model $model): void
    {
        $this->assertRefused(fn () => $model->delete(), 1451);
    }

    private function assertForceDeleteRefused(User $user): void
    {
        $this->assertRefused(fn () => $user->forceDelete(), 1451);
    }
}
