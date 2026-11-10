<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\User\Enums\Permission;
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
        $userFactory = User::factory();
        $this->admin = $userFactory->create();
        $this->admin->assignRole(
            Role::findByName('admin', 'backpack'),
        );
    }

    #[Test]
    public function a_guest_is_sent_to_the_admin_login(): void
    {
        $httpResponse = $this->get('/admin/bank-account');
        $httpResponse->assertRedirect(
            route('backpack.auth.login'),
        );
    }

    #[Test]
    public function bank_account_reads_are_scoped_to_the_permitted_owner_type(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create();
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create();
        $bankAccountFactory = BankAccount::factory();
        $ownedFactory = $bankAccountFactory->ownedBy($business);
        $businessAccount = $ownedFactory->create();
        $ownedFactory = $bankAccountFactory->ownedBy($contact);
        $contactAccount = $ownedFactory->create(['iban' => 'DE89370400440532013000']);
        foreach (
            [
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
            ] as [$type, $visible, $hidden]
        ) {
            $this->actingAs(
                $this->backendUser(
                    Permission::from($type . '.view'),
                ),
                'backpack',
            );
            foreach (
                [
                    '',
                    '?business_id=' . $business->id,
                    '?contact_id=' . $contact->id,
                ] as $filter
            ) {
                $response = $this->postJson('/admin/bank-account/search' . $filter, ['start' => 0, 'length' => 100]);
                $response->assertOk();
                $response->assertDontSee($hidden->iban);
                if ($filter === '') {
                    $response->assertSee($visible->iban);
                }
            }
            $this->actingAs(
                $this->backendUser(
                    Permission::from($type . '.update'),
                ),
                'backpack',
            );
            $hiddenResponse = $this->get('/admin/bank-account/' . $hidden->id . '/edit');
            $hiddenResponse->assertForbidden();
            $visibleResponse = $this->get('/admin/bank-account/' . $visible->id . '/edit');
            $visibleResponse->assertOk();
        }
    }

    #[Test]
    public function adds_the_first_account_of_a_contact_as_its_default(): void
    {
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create();

        $httpClient = $this->actingAs($this->admin, 'backpack');
        $httpResponse = $httpClient->post(
            '/admin/bank-account',
            [
                'contact_id' => $contact->id,
                'country' => 'CZ',
                'number' => '2001234561',
                'bank_code' => '2010',
                '_save_action' => 'save_and_back',
            ],
        );
        $httpResponse->assertRedirect();

        $bankAccountQuery = BankAccount::query();
        $bankAccountQuery->where('contact_id', $contact->id);
        $account = $bankAccountQuery->sole();
        $this->assertSame('CZ1520100000002001234561', $account->iban);
        $this->assertSame($account->id, $contact->fresh()?->default_bank_account_id);
    }

    #[Test]
    public function refuses_an_invalid_iban(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create();

        $httpClient = $this->actingAs($this->admin, 'backpack');
        $httpResponse = $httpClient->post(
            '/admin/bank-account',
            [
                'business_id' => $business->id,
                'iban' => 'CZ0000000000000000000000',
            ],
        );
        $httpResponse->assertSessionHasErrors('iban');
    }

    #[Test]
    public function archiving_the_only_account_leaves_the_owner_without_a_default(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create();
        $bankAccountFactory = BankAccount::factory();
        $bankAccountFactory = $bankAccountFactory->ownedBy($business);
        $account = $bankAccountFactory->create();
        $business->forceFill(['default_bank_account_id' => $account->id]);
        $business->save();

        $httpClient = $this->actingAs($this->admin, 'backpack');
        $httpResponse = $httpClient->post(
            route('admin.bank-accounts.archive', ['id' => $account->id]),
        );
        $httpResponse->assertRedirect();

        $this->assertNotNull($account->fresh()?->archived_at);
        $this->assertNull($business->fresh()?->default_bank_account_id);
    }

    #[Test]
    public function changing_an_account_needs_the_owners_update_permission(): void
    {
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create();
        $bankAccountFactory = BankAccount::factory();
        $bankAccountFactory = $bankAccountFactory->ownedBy($contact);
        $account = $bankAccountFactory->create();
        $backendUser = $this->backendUser(Permission::BUSINESS_UPDATE);

        $httpClient = $this->actingAs($backendUser, 'backpack');
        $httpResponse = $httpClient->post(
            route('admin.bank-accounts.archive', ['id' => $account->id]),
        );
        $httpResponse->assertForbidden();
    }
}
