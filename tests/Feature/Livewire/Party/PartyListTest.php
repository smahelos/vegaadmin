<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Party;

use App\Livewire\Party\BusinessList;
use App\Livewire\Party\ContactList;
use App\Models\Business;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

/**
 * BusinessList and ContactList share ListsParties: the contact list stands for both, the business list adds the
 * default mark.
 */
final class PartyListTest extends TestCase
{
    use BuildsParties;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        URL::defaults(['locale' => 'cs']);
        $this->user = $this->frontendUser();
        $this->actingAs($this->user);
    }

    #[Test]
    public function shows_only_the_users_active_contacts(): void
    {
        Contact::factory()->create(['user_id' => $this->user->id, 'name' => 'Active Mine']);
        Contact::factory()->archived()->create(['user_id' => $this->user->id, 'name' => 'Archived Mine']);
        Contact::factory()->create(['name' => 'Someone Else']);

        Livewire::test(ContactList::class)
            ->assertSee('Active Mine')
            ->assertDontSee('Archived Mine')
            ->assertDontSee('Someone Else');
    }

    #[Test]
    public function the_archive_tab_shows_archived_contacts(): void
    {
        Contact::factory()->create(['user_id' => $this->user->id, 'name' => 'Active Mine']);
        Contact::factory()->archived()->create(['user_id' => $this->user->id, 'name' => 'Archived Mine']);

        Livewire::test(ContactList::class)
            ->call('showArchived', true)
            ->assertSee('Archived Mine')
            ->assertDontSee('Active Mine');
    }

    #[Test]
    public function searches_the_name_short_name_company_number_and_email(): void
    {
        Contact::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Nakladatelství',
            'short_name' => 'NAK',
        ]);
        Contact::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Tiskárna',
            'email' => 'tisk@example.test',
        ]);

        Livewire::test(ContactList::class)
            ->set('search', 'NAK')
            ->assertSee('Nakladatelství')
            ->assertDontSee('Tiskárna')
            ->set('search', 'tisk@')
            ->assertSee('Tiskárna')
            ->assertDontSee('Nakladatelství');
    }

    #[Test]
    public function a_wildcard_in_the_search_is_taken_literally(): void
    {
        Contact::factory()->create(['user_id' => $this->user->id, 'name' => 'Plain Name']);

        Livewire::test(ContactList::class)
            ->set('search', '%')
            ->assertDontSee('Plain Name');
    }

    #[Test]
    public function the_business_list_marks_the_default_business(): void
    {
        $business = Business::factory()->create(['user_id' => $this->user->id, 'name' => 'Default One']);
        $this->user->forceFill(['default_business_id' => $business->id])->save();

        Livewire::test(BusinessList::class)
            ->assertSeeInOrder(['Default One', __('businesses.fields.default')]);
    }

    #[Test]
    public function a_list_needs_the_view_permission(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ContactList::class)->assertForbidden();
        Livewire::test(BusinessList::class)->assertForbidden();
    }
}
