<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Contact;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Support\BuildsBackendUsers;
use Tests\TestCase;

final class BankAccountCrudControllerTest extends TestCase
{
    use BuildsBackendUsers;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::findByName('admin', 'backpack'));
    }

    #[Test]
    public function a_guest_is_sent_to_the_admin_login(): void
    {
        $this->get('/admin/bank-account')->assertRedirect(route('backpack.auth.login'));
    }

    #[Test]
    public function bank_account_reads_are_scoped_to_the_permitted_owner_type(): void
    {
        $business = Business::factory()->create();
        $contact = Contact::factory()->create();
        $businessAccount = BankAccount::factory()->ownedBy($business)->create();
        $contactAccount = BankAccount::factory()->ownedBy($contact)->create(['iban' => 'DE89370400440532013000']);
        foreach ([
            [
                'business',
                $businessAccount,
                $contactAccount,
            ],
            [
                'contact',
                $contactAccount,
                $businessAccount,
            ],
        ] as [$type, $visible, $hidden]) {
            $this->actingAs($this->backendUser('can_view_' . $type), 'backpack');
            foreach ([
                '',
                '?business_id=' . $business->id,
                '?contact_id=' . $contact->id,
            ] as $filter) {
                $response = $this->postJson('/admin/bank-account/search' . $filter, ['start' => 0, 'length' => 100]);
                $response->assertOk()->assertDontSee($hidden->iban);
                if ($filter === '') {
                    $response->assertSee($visible->iban);
                }
            }
            $this->actingAs($this->backendUser('can_update_' . $type), 'backpack');
            $this->get('/admin/bank-account/' . $hidden->id . '/edit')->assertForbidden();
            $this->get('/admin/bank-account/' . $visible->id . '/edit')->assertOk();
        }
    }

    #[Test]
    public function adds_the_first_account_of_a_contact_as_its_default(): void
    {
        $contact = Contact::factory()->create();

        $this->actingAs($this->admin, 'backpack')
            ->post('/admin/bank-account', [
                'contact_id' => $contact->id,
                'country' => 'CZ',
                'number' => '2001234561',
                'bank_code' => '2010',
                '_save_action' => 'save_and_back',
            ])
            ->assertRedirect();

        $account = BankAccount::query()->where('contact_id', $contact->id)->sole();
        $this->assertSame('CZ1520100000002001234561', $account->iban);
        $this->assertSame($account->id, $contact->fresh()?->default_bank_account_id);
    }

    #[Test]
    public function refuses_an_invalid_iban(): void
    {
        $business = Business::factory()->create();

        $this->actingAs($this->admin, 'backpack')
            ->post('/admin/bank-account', [
                'business_id' => $business->id,
                'iban' => 'CZ0000000000000000000000',
            ])
            ->assertSessionHasErrors('iban');
    }

    #[Test]
    public function archiving_the_only_account_leaves_the_owner_without_a_default(): void
    {
        $business = Business::factory()->create();
        $account = BankAccount::factory()->ownedBy($business)->create();
        $business->forceFill(['default_bank_account_id' => $account->id])->save();

        $this->actingAs($this->admin, 'backpack')
            ->post(route('admin.bank-accounts.archive', ['id' => $account->id]))
            ->assertRedirect();

        $this->assertNotNull($account->fresh()?->archived_at);
        $this->assertNull($business->fresh()?->default_bank_account_id);
    }

    #[Test]
    public function changing_an_account_needs_the_owners_update_permission(): void
    {
        $contact = Contact::factory()->create();
        $account = BankAccount::factory()->ownedBy($contact)->create();
        $backendUser = $this->backendUser('can_update_business');

        $this->actingAs($backendUser, 'backpack')
            ->post(route('admin.bank-accounts.archive', ['id' => $account->id]))
            ->assertForbidden();
    }
}
