<?php

declare(strict_types=1);

namespace Tests\Feature\Livewire\Party;

use App\Livewire\Party\PartyListLatest;
use App\Models\Business;
use App\Models\Contact;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class PartyListLatestTest extends TestCase
{
    use BuildsParties;

    /** @return array<string, array{string, class-string<Business>|class-string<Contact>}> */
    public static function kinds(): array
    {
        return ['businesses' => ['business', Business::class], 'contacts' => ['contact', Contact::class]];
    }

    #[Test]
    #[DataProvider('kinds')]
    public function shows_only_the_five_latest_active_parties_and_a_link_to_the_full_list(string $kind, string $model): void
    {
        URL::defaults(['locale' => 'cs']);
        $user = $this->frontendUser();
        $modelFactory = $model::factory();
        $modelFactory->create(['name' => 'Foreign party']);
        $archivedFactory = $modelFactory->archived();
        $archivedFactory->create(['user_id' => $user->id, 'name' => 'Archived party']);
        for ($index = 1; $index <= 6; $index++) {
            $modelFactory->create(
                [
                    'user_id' => $user->id,
                    'name' => 'Party ' . $index,
                    'created_at' => now()->subDays(7 - $index),
                ],
            );
        }

        Livewire::actingAs($user);
        Livewire::withQueryParams(
            [
                'page' => 2,
                'contactPage' => 2,
                'businessPage' => 2,
            ],
        );
        $component = Livewire::test(PartyListLatest::class, ['partyType' => $kind]);
        $component->assertSeeInOrder(
            [
                'Party 6',
                'Party 5',
                'Party 4',
                'Party 3',
                'Party 2',
            ],
        );
        $component->assertDontSee('Party 1');
        $component->assertDontSee('Foreign party');
        $component->assertDontSee('Archived party');
        $component->assertSee(
            __('general.actions.view_all'),
        );
        $component->assertSee(
            route(
                'frontend.' . ($kind === 'business' ? 'businesses' : 'contacts') . '.index',
                ['locale' => 'cs'],
            ),
            false,
        );
        $component->assertDontSee(
            'aria-label="' . __('general.pagination.navigation') . '"',
            false,
        );

    }

    #[Test]
    #[DataProvider('kinds')]
    public function an_empty_widget_still_links_to_the_full_list(string $kind, string $model): void
    {
        URL::defaults(['locale' => 'cs']);
        $user = $this->frontendUser();
        $modelFactory = $model::factory();
        $archivedFactory = $modelFactory->archived();
        $archivedFactory->create(['user_id' => $user->id, 'name' => 'Archived only']);

        Livewire::actingAs($user);
        $component = Livewire::test(PartyListLatest::class, ['partyType' => $kind]);
        $component->assertSee(
            __('general.empty.no_results'),
        );
        $component->assertSee(
            __('general.actions.view_all'),
        );
        $component->assertDontSee('Archived only');
    }

    #[Test]
    #[DataProvider('kinds')]
    public function equal_creation_times_are_ordered_by_the_newest_id(string $kind, string $model): void
    {
        URL::defaults(['locale' => 'cs']);
        $user = $this->frontendUser();
        $now = CarbonImmutable::now();
        $created = $now->subDay();
        $modelFactory = $model::factory();
        $modelFactory->create(
            [
                'user_id' => $user->id,
                'name' => 'Alpha older',
                'created_at' => $created,
            ],
        );
        $modelFactory->create(
            [
                'user_id' => $user->id,
                'name' => 'Zulu newer',
                'created_at' => $created,
            ],
        );

        Livewire::actingAs($user);
        $component = Livewire::test(PartyListLatest::class, ['partyType' => $kind]);
        $component->assertSeeInOrder(['Zulu newer', 'Alpha older']);
    }

    #[Test]
    #[DataProvider('kinds')]
    public function requires_an_authenticated_user_with_the_view_permission(string $kind, string $model): void
    {
        $userFactory = User::factory();
        $user = $userFactory->create();
        $modelFactory = $model::factory();
        $modelFactory->create(['user_id' => $user->id]);

        $component = Livewire::test(PartyListLatest::class, ['partyType' => $kind]);
        $component->assertForbidden();
        Livewire::actingAs($user);
        $component = Livewire::test(PartyListLatest::class, ['partyType' => $kind]);
        $component->assertForbidden();
    }
}
