<?php

declare(strict_types=1);

namespace Tests\Feature\View\Components;

use App\Domain\Shared\Unit\Enums\Unit;
use App\View\Components\UnitOptions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class UnitOptionsTest extends TestCase
{
    #[Test]
    public function lists_every_unit_with_its_translated_label(): void
    {
        app()->setLocale('cs');

        $view = $this->component(UnitOptions::class);

        $this->assertSame(count(Unit::cases()), substr_count((string) $view, '<option '));
        $view->assertSeeInOrder(['value="cubic_meters"', '>m³</option>'], false);
        $view->assertSeeInOrder(['value="hours"', '>hodin</option>'], false);
    }

    #[Test]
    public function preselects_pieces_by_default(): void
    {
        $this->component(UnitOptions::class)
            ->assertSee('<option value="pieces" selected>', false);
    }

    #[Test]
    public function preselects_the_given_unit(): void
    {
        $view = $this->component(UnitOptions::class, ['selected' => Unit::KILOGRAMS]);

        $view->assertSee('<option value="kilograms" selected>', false);
        $view->assertDontSee('<option value="pieces" selected>', false);
    }
}
