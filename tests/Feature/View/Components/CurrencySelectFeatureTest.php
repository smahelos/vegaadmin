<?php

declare(strict_types=1);

namespace Tests\Feature\View\Components;

use App\Domain\Shared\Money\Enums\Currency;
use App\View\Components\CurrencySelect;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CurrencySelectFeatureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // The view prints validation errors, which the web middleware shares outside a test
        $this->withViewErrors([]);
    }

    #[Test]
    public function offers_every_currency_of_the_enum(): void
    {
        $view = $this->component(
            CurrencySelect::class,
            [
                'name' => 'currency',
                'label' => 'Měna',
            ],
        );

        $this->assertSame(
            count(Currency::cases()),
            substr_count((string) $view, '<option '),
        );
        $view->assertSee('<option value="CHF"', false);
    }

    #[Test]
    public function preselects_czk_by_default(): void
    {
        $view = $this->component(
            CurrencySelect::class,
            [
                'name' => 'currency',
                'label' => 'Měna',
            ],
        );

        $view->assertSee('<option value="CZK" selected>', false);
    }

    #[Test]
    public function preselects_the_given_currency(): void
    {
        $view = $this->component(
            CurrencySelect::class,
            [
                'name' => 'currency',
                'label' => 'Měna',
                'selected' => 'EUR',
            ],
        );

        $view->assertSee('<option value="EUR" selected>', false);
        $view->assertDontSee('<option value="CZK" selected>', false);
    }
}
