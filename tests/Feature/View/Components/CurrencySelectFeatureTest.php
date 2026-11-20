<?php

declare(strict_types=1);

namespace Tests\Feature\View\Components;

use App\Domain\Shared\Money\Enums\Currency;
use App\View\Components\CurrencySelect;
use PHPUnit\Framework\Attributes\DataProvider;
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
    public function labels_each_currency_with_its_code_and_name(): void
    {
        $view = $this->component(
            CurrencySelect::class,
            [
                'name' => 'currency',
                'label' => 'Měna',
            ],
        );

        $view->assertSee('CZK – česká koruna');
        $view->assertSee('USD – americký dolar');
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

    /**
     * @return array<string, array{array<mixed>|string|null}>
     */
    public static function unusableSubmits(): array
    {
        return [
            'empty' => [null],
            'array' => [['EUR']],
            'unknown code' => ['XYZ'],
        ];
    }

    /**
     * @param  array<mixed>|string|null  $submitted
     */
    #[Test]
    #[DataProvider('unusableSubmits')]
    public function renders_without_a_selection_after_an_unusable_submit(array|string|null $submitted): void
    {
        $view = $this->component(
            CurrencySelect::class,
            [
                'name' => 'currency',
                'label' => 'Měna',
                'selected' => $submitted,
            ],
        );

        $this->assertSame(
            count(Currency::cases()),
            substr_count((string) $view, '<option '),
        );
        $view->assertDontSee(' selected>', false);
    }

    #[Test]
    public function prints_the_hint(): void
    {
        $view = $this->component(
            CurrencySelect::class,
            [
                'name' => 'currency',
                'label' => 'Měna',
                'hint' => 'Měna, ve které faktura požaduje platbu',
            ],
        );

        $view->assertSee('Měna, ve které faktura požaduje platbu');
    }
}
