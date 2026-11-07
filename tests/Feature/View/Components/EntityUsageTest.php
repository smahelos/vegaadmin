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
    public function shows_the_usage_against_the_limit_with_a_meter(): void
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
        // Yellow from 4 of 5, red only at 5 of 5
        $view->assertSee(
            'max="5" low="4" high="4.5" optimum="0" value="4"',
            false,
        );
    }

    #[Test]
    public function shows_an_unlimited_value_without_a_meter(): void
    {
        $owner = $this->frontendUser();
        $this->limitFrontendUser(LimitedEntity::INVOICE, null);
        $this->actingAs($owner, 'web');

        $view = $this->blade(
            self::TEMPLATE,
            ['entity' => LimitedEntity::INVOICE],
        );

        $view->assertSee(
            __('role_limits.usage.unlimited'),
        );
        $view->assertDontSee('<meter', false);
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
            __('role_limits.usage.not_allowed'),
        );
        $view->assertDontSee('<meter', false);
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
