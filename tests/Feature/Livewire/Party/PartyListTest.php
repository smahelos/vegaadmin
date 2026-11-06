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
        $contactFactory = Contact::factory();
        $contactFactory->create(['user_id' => $this->user->id, 'name' => 'Active Mine']);
        $archivedFactory = $contactFactory->archived();
        $archivedFactory->create(['user_id' => $this->user->id, 'name' => 'Archived Mine']);
        $contactFactory->create(['name' => 'Someone Else']);

        $component = Livewire::test(ContactList::class);
        $component->assertSee('Active Mine');
        $component->assertDontSee('Archived Mine');
        $component->assertDontSee('Someone Else');
    }

    #[Test]
    public function the_archive_tab_shows_archived_contacts(): void
    {
        $contactFactory = Contact::factory();
        $contactFactory->create(['user_id' => $this->user->id, 'name' => 'Active Mine']);
        $archivedFactory = $contactFactory->archived();
        $archivedFactory->create(['user_id' => $this->user->id, 'name' => 'Archived Mine']);

        $component = Livewire::test(ContactList::class);
        $component->call('showArchived', true);
        $component->assertSee('Archived Mine');
        $component->assertDontSee('Active Mine');
    }

    #[Test]
    public function searches_the_name_short_name_company_number_and_email(): void
    {
        $contactFactory = Contact::factory();
        $contactFactory->create(
            [
                'user_id' => $this->user->id,
                'name' => 'Nakladatelství',
                'short_name' => 'NAK',
            ],
        );
        $contactFactory->create(
            [
                'user_id' => $this->user->id,
                'name' => 'Tiskárna',
                'email' => 'tisk@example.test',
            ],
        );

        $component = Livewire::test(ContactList::class);
        $component->set('search', 'NAK');
        $component->assertSee('Nakladatelství');
        $component->assertDontSee('Tiskárna');
        $component->set('search', 'tisk@');
        $component->assertSee('Tiskárna');
        $component->assertDontSee('Nakladatelství');
    }

    #[Test]
    public function a_wildcard_in_the_search_is_taken_literally(): void
    {
        $contactFactory = Contact::factory();
        $contactFactory->create(['user_id' => $this->user->id, 'name' => 'Plain Name']);

        $component = Livewire::test(ContactList::class);
        $component->set('search', '%');
        $component->assertDontSee('Plain Name');
    }

    #[Test]
    public function the_business_list_marks_the_default_business(): void
    {
        $businessFactory = Business::factory();
        $business = $businessFactory->create(['user_id' => $this->user->id, 'name' => 'Default One']);
        $this->user->forceFill(['default_business_id' => $business->id]);
        $this->user->save();

        $component = Livewire::test(BusinessList::class);
        $component->assertSeeInOrder(
            ['Default One', __('businesses.fields.default')],
        );
    }

    #[Test]
    public function a_list_needs_the_view_permission(): void
    {
        $userFactory = User::factory();
        $this->actingAs($userFactory->create());

        $component = Livewire::test(ContactList::class);
        $component->assertForbidden();
        $component = Livewire::test(BusinessList::class);
        $component->assertForbidden();
    }

    #[Test]
    public function pagination_uses_the_shared_controls_and_resets_after_filtering(): void
    {
        $contactFactory = Contact::factory();
        for ($index = 1; $index <= 11; $index++) {
            $contactFactory->create(
                [
                    'user_id' => $this->user->id,
                    'name' => sprintf('Contact %02d', $index),
                ],
            );
        }

        $component = Livewire::test(ContactList::class);
        $component->assertSee('Contact 01');
        $component->assertDontSee('Contact 11');
        $component->assertSee(
            'aria-label="' . __('general.pagination.navigation') . '"',
            false,
        );
        $component->call('setPage', 2);
        $component->assertSee('Contact 11');
        $component->assertDontSee('Contact 01');
        $component->set('search', 'Contact 01');
        $component->assertSee('Contact 01');
        $component->assertViewHas(
            'contacts',
            fn ($contacts): bool => $contacts->currentPage() === 1 && $contacts->total() === 1,
        );
    }
}
