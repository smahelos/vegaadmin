<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Frontend;

use App\Models\Business;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

/**
 * The business and contact pages of BusinessController and ContactController.
 */
final class PartyPagesTest extends TestCase
{
    use BuildsParties;

    protected function setUp(): void
    {
        parent::setUp();
        URL::defaults(['locale' => 'cs']);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function listPages(): iterable
    {
        yield 'businesses index' => ['frontend.businesses.index', 'frontend.businesses.create'];
        yield 'contacts index' => ['frontend.contacts.index', 'frontend.contacts.create'];
    }

    /**
     * @return iterable<string, array{class-string<Business|Contact>, string, string, string}>
     */
    public static function recordPages(): iterable
    {
        yield 'business detail' => [
            Business::class,
            'frontend.businesses.show',
            'business',
            'party.business-detail',
        ];
        yield 'business edit' => [
            Business::class,
            'frontend.businesses.edit',
            'business',
            'party.business-form',
        ];
        yield 'contact detail' => [
            Contact::class,
            'frontend.contacts.show',
            'contact',
            'party.contact-detail',
        ];
        yield 'contact edit' => [
            Contact::class,
            'frontend.contacts.edit',
            'contact',
            'party.contact-form',
        ];
    }

    #[Test]
    #[DataProvider('listPages')]
    public function a_guest_is_sent_to_the_login(string $index, string $create): void
    {
        $this->get(route($index))->assertRedirect('/login');
        $this->get(route($create))->assertRedirect('/login');
    }

    #[Test]
    #[DataProvider('listPages')]
    public function a_user_without_the_permissions_is_forbidden(string $index, string $create): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route($index))->assertForbidden();
        $this->get(route($create))->assertForbidden();
    }

    #[Test]
    #[DataProvider('listPages')]
    public function a_frontend_user_opens_the_list_and_the_create_form(string $index, string $create): void
    {
        $this->actingAs($this->frontendUser());

        $this->get(route($index))->assertOk();
        $this->get(route($create))->assertOk();
    }

    /**
     * @param  class-string<Business|Contact>  $model
     */
    #[Test]
    #[DataProvider('recordPages')]
    public function a_user_opens_their_own_record(
        string $model,
        string $route,
        string $parameter,
        string $component,
    ): void {
        $user = $this->frontendUser();
        $record = $model::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route($route, [$parameter => $record->id]))
            ->assertOk()
            ->assertSeeLivewire($component);
    }

    /**
     * @param  class-string<Business|Contact>  $model
     */
    #[Test]
    #[DataProvider('recordPages')]
    public function another_users_record_is_not_found(
        string $model,
        string $route,
        string $parameter,
    ): void {
        $foreign = $model::factory()->create();

        $this->actingAs($this->frontendUser())
            ->get(route($route, [$parameter => $foreign->id]))
            ->assertNotFound();
    }

    /**
     * @param  class-string<Business|Contact>  $model
     */
    #[Test]
    #[DataProvider('recordPages')]
    public function a_guest_cannot_open_a_record(
        string $model,
        string $route,
        string $parameter,
    ): void {
        $record = $model::factory()->create();

        $this->get(route($route, [$parameter => $record->id]))->assertRedirect('/login');
    }
}
