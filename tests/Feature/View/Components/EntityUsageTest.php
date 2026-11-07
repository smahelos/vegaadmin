<?php

declare(strict_types=1);

namespace Tests\Feature\View\Components;

use App\Domain\User\Enums\LimitedEntity;
use App\Models\Contact;
use PHPUnit\Framework\Attributes\Test;
use Tests\Support\BuildsParties;
use Tests\TestCase;

final class EntityUsageTest extends TestCase
{
    use BuildsParties;

    private const TEMPLATE = '<x-entity-usage :entity="$entity" />';

    #[Test]
    public function warns_from_eighty_percent_of_the_limit(): void
    {
        $owner = $this->frontendUser();
        $this->limitFrontendUser(LimitedEntity::CONTACT, 5);
        $contactFactory = Contact::factory(4);
        $contactFactory->create(['user_id' => $owner->id]);
        $archivedFactory = Contact::factory();
        $archivedFactory = $archivedFactory->archived();
        $archivedFactory->create(['user_id' => $owner->id]);
        $this->actingAs($owner, 'web');

        $view = $this->blade(
            self::TEMPLATE,
            ['entity' => LimitedEntity::CONTACT],
        );

        $view->assertSee(
            __('role_limits.usage.contacts'),
        );
        $view->assertSee('4 / 5');
        $view->assertSee('max="5" value="4"', false);
        $view->assertSee(
            __('role_limits.usage.approaching'),
        );
        $view->assertDontSee(
            __('role_limits.usage.reached'),
        );
    }

    #[Test]
    public function shows_a_reached_limit_with_a_link_to_the_plans(): void
    {
        $owner = $this->frontendUser();
        $this->limitFrontendUser(LimitedEntity::CONTACT, 2);
        $contactFactory = Contact::factory(2);
        $contactFactory->create(['user_id' => $owner->id]);
        $this->actingAs($owner, 'web');

        $view = $this->blade(
            self::TEMPLATE,
            ['entity' => LimitedEntity::CONTACT],
        );

        $view->assertSee('2 / 2');
        $view->assertSee(
            __('role_limits.usage.reached'),
        );
        $view->assertSee(
            __('subscriptions.actions.view_plans'),
        );
    }

    #[Test]
    public function shows_no_warning_below_eighty_percent(): void
    {
        $owner = $this->frontendUser();
        $this->limitFrontendUser(LimitedEntity::CONTACT, 10);
        $contactFactory = Contact::factory(7);
        $contactFactory->create(['user_id' => $owner->id]);
        $this->actingAs($owner, 'web');

        $view = $this->blade(
            self::TEMPLATE,
            ['entity' => LimitedEntity::CONTACT],
        );

        $view->assertSee('7 / 10');
        $view->assertDontSee(
            __('subscriptions.actions.view_plans'),
        );
    }

    #[Test]
    public function shows_an_unlimited_value_without_a_bar(): void
    {
        $owner = $this->frontendUser();
        $this->limitFrontendUser(LimitedEntity::INVOICE, null);
        $this->actingAs($owner, 'web');

        $view = $this->blade(
            self::TEMPLATE,
            ['entity' => LimitedEntity::INVOICE],
        );

        $view->assertSee(
            __('role_limits.statuses.unlimited'),
        );
        $view->assertDontSee('<progress', false);
    }

    #[Test]
    public function shows_a_zero_limit_as_not_allowed(): void
    {
        $owner = $this->frontendUser();
        $this->limitFrontendUser(LimitedEntity::EXPENSE, 0);
        $this->actingAs($owner, 'web');

        $view = $this->blade(
            self::TEMPLATE,
            ['entity' => LimitedEntity::EXPENSE],
        );

        $view->assertSee(
            __('role_limits.statuses.not_allowed'),
        );
        $view->assertDontSee('<progress', false);
    }

    #[Test]
    public function renders_nothing_for_a_guest(): void
    {
        $view = $this->blade(
            self::TEMPLATE,
            ['entity' => LimitedEntity::INVOICE],
        );

        $this->assertSame(
            '',
            trim((string) $view),
        );
    }
}
