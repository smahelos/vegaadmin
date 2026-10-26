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
        $invoice = Invoice::factory()->create();
        $business = Business::findOrFail($invoice->business_id);
        $account = BankAccount::factory()->ownedBy($business)->create();
        $contact = Contact::factory()->create(['user_id' => $invoice->user_id]);
        $invoice->forceFill(['contact_id' => $contact->id, 'bank_account_id' => $account->id])->save();

        $this->assertDeleteRefused($business);
        $this->assertDeleteRefused($contact);
        $this->assertDeleteRefused($account);
        $this->assertDeleteRefused($invoice->paymentMethod);
        $this->assertDeleteRefused(Status::findOrFail($invoice->payment_status_id));
        $this->assertForceDeleteRefused(User::findOrFail($invoice->user_id));

        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'business_id' => $business->id,
            'contact_id' => $contact->id,
            'bank_account_id' => $account->id,
            'payment_method_id' => $invoice->payment_method_id,
            'payment_status_id' => $invoice->payment_status_id,
            'user_id' => $invoice->user_id,
        ]);
    }

    #[Test]
    public function an_invoice_line_keeps_its_product(): void
    {
        $line = InvoiceProduct::factory()->create();

        $this->assertDeleteRefused(Product::findOrFail($line->product_id));
        $this->assertDatabaseHas('invoice_products', ['id' => $line->id, 'product_id' => $line->product_id]);
    }

    #[Test]
    public function the_same_product_can_be_on_several_lines_of_one_invoice(): void
    {
        $line = InvoiceProduct::factory()->create();

        InvoiceProduct::factory()->create([
            'invoice_id' => $line->invoice_id,
            'product_id' => $line->product_id,
        ]);

        $this->assertSame(2, InvoiceProduct::where('invoice_id', $line->invoice_id)->count());
    }

    #[Test]
    public function deleting_an_invoice_deletes_its_lines(): void
    {
        $line = InvoiceProduct::factory()->create();

        Invoice::findOrFail($line->invoice_id)->delete();

        $this->assertDatabaseMissing('invoice_products', ['id' => $line->id]);
    }

    #[Test]
    public function a_product_keeps_its_tax_rate_and_category(): void
    {
        $product = Product::factory()->create();

        $this->assertDeleteRefused(Tax::findOrFail($product->tax_id));
        $this->assertDeleteRefused($product->category);
    }

    #[Test]
    public function an_expense_keeps_its_business_contact_account_category_payment_method_and_status(): void
    {
        $expense = Expense::factory()->create(['status_id' => Status::factory()]);
        $contact = Contact::factory()->create(['user_id' => $expense->user_id]);
        $account = BankAccount::factory()->ownedBy($contact)->create();
        $expense->forceFill(['contact_id' => $contact->id, 'bank_account_id' => $account->id])->save();

        $this->assertDeleteRefused(Business::findOrFail($expense->business_id));
        $this->assertDeleteRefused($contact);
        $this->assertDeleteRefused($account);
        $this->assertDeleteRefused($expense->category);
        $this->assertDeleteRefused($expense->paymentMethod);
        $this->assertDeleteRefused(Status::findOrFail($expense->status_id));

        $expense->delete();
        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    #[Test]
    public function billex_payments_keep_their_subscription_plan_and_user(): void
    {
        $payment = Payment::factory()->create();
        $subscription = $payment->subscription;

        $this->assertDeleteRefused($subscription);
        $this->assertDeleteRefused(SubscriptionPlan::findOrFail($subscription->subscription_plan_id));
        $this->assertForceDeleteRefused(User::findOrFail($subscription->user_id));
    }

    #[Test]
    public function deleting_a_plan_feature_removes_it_from_plans(): void
    {
        $plan = SubscriptionPlan::factory()->create();
        $feature = SubscriptionPlanFeature::factory()->create();
        DB::table('subscription_plan_subscription_plan_feature')->insert([
            'subscription_plan_id' => $plan->id,
            'subscription_plan_feature_id' => $feature->id,
        ]);

        $feature->forceDelete();

        $this->assertDatabaseMissing('subscription_plan_subscription_plan_feature', ['subscription_plan_id' => $plan->id]);
    }

    #[Test]
    public function product_slugs_are_unique_per_user(): void
    {
        $product = Product::factory()->create();
        $otherUser = User::factory()->create();

        Product::factory()->create(['user_id' => $otherUser->id, 'slug' => $product->slug]);

        $this->assertRefused(fn () => Product::factory()->create(['user_id' => $product->user_id, 'slug' => $product->slug]), 1062);
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
