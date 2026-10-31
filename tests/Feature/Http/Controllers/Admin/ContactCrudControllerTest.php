<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

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
        $this->admin = User::factory()->create();
        $this->admin->assignRole(Role::findByName('admin', 'backpack'));
    }

    #[Test]
    public function a_guest_is_sent_to_the_admin_login(): void
    {
        $this->get('/admin/contact')->assertRedirect(route('backpack.auth.login'));
    }

    #[Test]
    public function creates_a_contact_for_the_chosen_owner(): void
    {
        $owner = $this->frontendUser();

        $this->actingAs($this->admin, 'backpack')
            ->post('/admin/contact', [
                'user_id' => $owner->id,
                'name' => 'Admin Kontakt',
                'contact_person' => 'Eva',
                'country' => 'CZ',
                '_save_action' => 'save_and_back',
            ])
            ->assertRedirect();

        $contact = Contact::query()->where('user_id', $owner->id)->sole();
        $this->assertSame('Eva', $contact->contact_person);
    }

    #[Test]
    public function the_owners_capacity_limit_applies_in_the_admin(): void
    {
        $owner = $this->frontendUser();
        $this->limitActive('contact', 0);

        $this->actingAs($this->admin, 'backpack')
            ->post('/admin/contact', [
                'user_id' => $owner->id,
                'name' => 'Nad limit',
                'country' => 'CZ',
            ])
            ->assertRedirect();

        $this->assertSame(0, Contact::query()->where('user_id', $owner->id)->count());
    }

    #[Test]
    public function archives_and_restores_a_contact(): void
    {
        $contact = Contact::factory()->create(['user_id' => $this->frontendUser()->id]);

        $this->actingAs($this->admin, 'backpack')
            ->post(route('admin.contacts.archive', ['id' => $contact->id]))
            ->assertRedirect();
        $this->assertNotNull($contact->fresh()?->archived_at);

        $this->post(route('admin.contacts.restore', ['id' => $contact->id]))->assertRedirect();
        $this->assertNull($contact->fresh()?->archived_at);
    }

    #[Test]
    public function updating_needs_the_permission(): void
    {
        $contact = Contact::factory()->create();
        $backendUser = $this->backendUser('can_view_contact');

        $this->actingAs($backendUser, 'backpack')
            ->put('/admin/contact/' . $contact->id, [
                'id' => $contact->id,
                'name' => 'Změna',
            ])
            ->assertForbidden();
    }
}
