<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Invoice;

use App\Domain\Invoice\Exceptions\InvoiceNotFoundException;
use App\Domain\User\Enums\LimitedEntity;
use App\Domain\User\Enums\Permission;
use App\Livewire\Invoice\IssueInvoiceDialog;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\App;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

/**
 * The issue dialog of the invoice list and detail (D-050, point 10): who may issue and what the dialog shows.
 */
final class IssueInvoiceDialogTest extends TestCase
{
    use BuildsParties;

    private User $user;

    private Invoice $draft;

    private int $year;

    private string $today;

    protected function setUp(): void
    {
        parent::setUp();

        $now = CarbonImmutable::now();
        $this->year = $now->year;
        $this->today = $now->toDateString();
        $this->user = $this->frontendUser();
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $this->user->id]);
        $invoiceFactory = Invoice::factory();
        $invoiceFactory = $invoiceFactory->draft();
        $this->draft = $invoiceFactory->create(
            [
                'user_id' => $this->user->id,
                'business_id' => $business->id,
            ],
        );
    }

    #[Test]
    public function opening_suggests_the_next_number_and_today(): void
    {
        $this->actingAs($this->user, 'web');

        $component = Livewire::test(IssueInvoiceDialog::class);
        $component->dispatch('open-issue-dialog', id: $this->draft->id);

        $component->assertSet('invoiceId', $this->draft->id);
        $component->assertSet('number', $this->year . '0001');
        $component->assertSet('issueDate', $this->today);
        $component->assertSee(
            __('invoices.titles.issue'),
        );
    }

    #[Test]
    public function the_detail_of_a_saved_draft_opens_the_dialog(): void
    {
        $this->actingAs($this->user, 'web');

        $component = Livewire::test(
            IssueInvoiceDialog::class,
            ['openFor' => $this->draft->id],
        );

        $component->assertSet('invoiceId', $this->draft->id);
    }

    #[Test]
    public function issuing_redirects_to_the_detail(): void
    {
        $this->actingAs($this->user, 'web');

        $component = Livewire::test(IssueInvoiceDialog::class);
        $component->dispatch('open-issue-dialog', id: $this->draft->id);
        $component->call('issue');

        $component->assertHasNoErrors();
        $component->assertRedirect(
            route('frontend.invoice.show', ['locale' => App::getLocale(), 'id' => $this->draft->id]),
        );
        $this->assertSame($this->year . '0001', $this->draft->fresh()->invoice_vs);
    }

    #[Test]
    public function a_number_of_another_year_shows_the_suggestion(): void
    {
        $this->actingAs($this->user, 'web');

        $component = Livewire::test(IssueInvoiceDialog::class);
        $component->dispatch('open-issue-dialog', id: $this->draft->id);
        $component->set('number', ($this->year - 1) . '0001');
        $component->call('issue');

        $component->assertHasErrors('number');
        $component->assertSee(
            __('invoices.validation.number_year', ['suggestion' => $this->year . '0001']),
        );
        $this->assertNull($this->draft->fresh()->issued_at);
    }

    #[Test]
    public function a_taken_number_shows_the_next_free_one(): void
    {
        $invoiceFactory = Invoice::factory();
        $invoiceFactory->create(
            [
                'user_id' => $this->user->id,
                'invoice_vs' => $this->year . '0001',
                'issue_date' => $this->today,
            ],
        );
        $this->actingAs($this->user, 'web');

        $component = Livewire::test(IssueInvoiceDialog::class);
        $component->dispatch('open-issue-dialog', id: $this->draft->id);
        $component->set('number', $this->year . '0001');
        $component->call('issue');

        $component->assertHasErrors('number');
        $component->assertSee(
            __('invoices.validation.number_taken', ['suggestion' => $this->year . '0002']),
        );
    }

    #[Test]
    public function the_monthly_quota_is_shown_in_the_dialog(): void
    {
        $this->limitFrontendUser(LimitedEntity::INVOICE, 0);
        $this->actingAs($this->user, 'web');

        $component = Livewire::test(IssueInvoiceDialog::class);
        $component->dispatch('open-issue-dialog', id: $this->draft->id);
        $component->call('issue');

        $component->assertSet(
            'limitMessage',
            __('invoices.messages.limit_exceeded', ['limit' => 0]),
        );
        $this->assertNull($this->draft->fresh()->issued_at);
    }

    #[Test]
    public function an_issued_invoice_returns_to_its_detail(): void
    {
        $invoiceFactory = Invoice::factory();
        $issued = $invoiceFactory->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user, 'web');

        $component = Livewire::test(IssueInvoiceDialog::class);
        $component->dispatch('open-issue-dialog', id: $issued->id);

        $component->assertSet('invoiceId', null);
        $component->assertRedirect(
            route('frontend.invoice.show', ['locale' => App::getLocale(), 'id' => $issued->id]),
        );
    }

    #[Test]
    public function a_vat_rate_of_a_business_that_stopped_paying_vat_returns_to_the_detail(): void
    {
        // setUp's business is a non-payer, so a line with a rate stands for a business changed after the save
        $invoiceProductFactory = InvoiceProduct::factory();
        $invoiceProductFactory->create(
            [
                'invoice_id' => $this->draft->id,
                'product_id' => null,
                'is_custom_product' => true,
                'name' => 'Konzultace',
                'quantity' => '1',
                'price' => '100',
                'currency' => $this->draft->payment_currency,
                'tax_rate' => '21',
            ],
        );
        $this->actingAs($this->user, 'web');

        $component = Livewire::test(IssueInvoiceDialog::class);
        $component->dispatch('open-issue-dialog', id: $this->draft->id);
        $component->call('issue');

        $component->assertRedirect(
            route('frontend.invoice.show', ['locale' => App::getLocale(), 'id' => $this->draft->id]),
        );
        $this->assertSame(
            __('invoices.validation.non_payer_vat_rate'),
            session('error'),
        );
        $this->assertNull($this->draft->fresh()->issued_at);
    }

    #[Test]
    public function a_user_without_the_permission_is_forbidden(): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $user->givePermissionTo(Permission::INVOICE_VIEW);
        $this->actingAs($user, 'web');

        $component = Livewire::test(IssueInvoiceDialog::class);
        $component->dispatch('open-issue-dialog', id: $this->draft->id);

        $component->assertForbidden();
    }

    #[Test]
    public function another_users_draft_is_not_found(): void
    {
        $this->actingAs($this->frontendUser(), 'web');

        $component = Livewire::test(IssueInvoiceDialog::class);
        // The exception handler answers it with 404
        $this->expectException(InvoiceNotFoundException::class);

        $component->dispatch('open-issue-dialog', id: $this->draft->id);
    }

    #[Test]
    public function the_invoice_id_cannot_be_changed_by_the_browser(): void
    {
        $this->actingAs($this->user, 'web');

        $component = Livewire::test(IssueInvoiceDialog::class);
        $component->dispatch('open-issue-dialog', id: $this->draft->id);

        $this->expectException(CannotUpdateLockedPropertyException::class);
        $component->set('invoiceId', 999);
    }
}
