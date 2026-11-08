<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Domain\User\Enums\LimitedEntity;
use App\Models\Contact;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\Support\BuildsBackendUsers;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class ContactCrudControllerTest extends TestCase
{
    use BuildsBackendUsers;
    use BuildsParties;

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
        $httpResponse = $this->get('/admin/contact');
        $httpResponse->assertRedirect(
            route('backpack.auth.login'),
        );
    }

    #[Test]
    public function creates_a_contact_for_the_chosen_owner(): void
    {
        $owner = $this->frontendUser();

        $httpClient = $this->actingAs($this->admin, 'backpack');
        $httpResponse = $httpClient->post(
            '/admin/contact',
            [
                'user_id' => $owner->id,
                'name' => 'Admin Kontakt',
                'contact_person' => 'Eva',
                'country' => 'CZ',
                '_save_action' => 'save_and_back',
            ],
        );
        $httpResponse->assertRedirect();

        $contactQuery = Contact::query();
        $contactQuery->where('user_id', $owner->id);
        $contact = $contactQuery->sole();
        $this->assertSame('Eva', $contact->contact_person);
    }

    #[Test]
    public function the_owners_capacity_limit_applies_in_the_admin(): void
    {
        $owner = $this->frontendUser();
        $this->limitFrontendUser(LimitedEntity::CONTACT, 0);

        $httpClient = $this->actingAs($this->admin, 'backpack');
        $httpResponse = $httpClient->post(
            '/admin/contact',
            [
                'user_id' => $owner->id,
                'name' => 'Nad limit',
                'country' => 'CZ',
            ],
        );
        $httpResponse->assertRedirect();

        $contactQuery = Contact::query();
        $contactQuery->where('user_id', $owner->id);
        $this->assertSame(
            0,
            $contactQuery->count(),
        );
    }

    #[Test]
    public function archives_and_restores_a_contact(): void
    {
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create(['user_id' => $this->frontendUser()->id]);

        $httpClient = $this->actingAs($this->admin, 'backpack');
        $httpResponse = $httpClient->post(
            route('admin.contacts.archive', ['id' => $contact->id]),
        );
        $httpResponse->assertRedirect();
        $this->assertNotNull($contact->fresh()?->archived_at);

        $httpResponse = $this->post(
            route('admin.contacts.restore', ['id' => $contact->id]),
        );
        $httpResponse->assertRedirect();
        $this->assertNull($contact->fresh()?->archived_at);
    }

    #[Test]
    public function updating_needs_the_permission(): void
    {
        $contactFactory = Contact::factory();
        $contact = $contactFactory->create();
        $backendUser = $this->backendUser('can_view_contact');

        $httpClient = $this->actingAs($backendUser, 'backpack');
        $httpResponse = $httpClient->put('/admin/contact/' . $contact->id, [
            'id' => $contact->id,
            'name' => 'Změna',
        ]);
        $httpResponse->assertForbidden();
    }
}
