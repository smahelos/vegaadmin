<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Shared;

use App\Livewire\Party\ContactList;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class PaginatesTest extends TestCase
{
    use BuildsParties;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $application = app();
        $application->setLocale('cs');
        URL::defaults(['locale' => 'cs']);
        $this->user = $this->frontendUser();
        $contactFactory = Contact::factory();
        $contactFactory = $contactFactory->count(15);
        $contactFactory->create(['user_id' => $this->user->id]);
    }

    #[Test]
    public function list_shows_translated_pagination(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ContactList::class);
        $component->assertSeeText('Zobrazeno');
        $component->assertSee('aria-label="Další"', false);
        $component->assertDontSeeText('Showing');
        $component->assertDontSeeText('results');
    }

    #[Test]
    public function next_page_moves_the_list_without_a_page_link(): void
    {
        $this->actingAs($this->user);

        $component = Livewire::test(ContactList::class);
        $component->assertSee("wire:click=\"nextPage('page')\"", false);
        $component->assertDontSee('href="?page=', false);
        $component->call('nextPage');
        $component->assertSee('aria-current="page">2</span>', false);
        $component->assertSee('<span class="font-medium">11</span>', false);
    }

    #[Test]
    public function page_from_the_url_that_is_not_a_number_shows_the_first_page(): void
    {
        $this->actingAs($this->user);

        Livewire::withQueryParams(['page' => 'abc']);
        $component = Livewire::test(ContactList::class);
        $component->assertOk();
        $component->assertSee('aria-current="page">1</span>', false);
    }
}
