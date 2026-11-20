<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Shared\Money\Presenters;

use App\Application\Shared\Money\Presenters\CurrencyOptionsPresenter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fakes\Shared\LocaleTaggedCurrencyNames;

final class CurrencyOptionsPresenterTest extends TestCase
{
    #[Test]
    public function labels_every_currency_with_its_code_and_name_czk_first_usd_last(): void
    {
        $presenter = new CurrencyOptionsPresenter(new LocaleTaggedCurrencyNames);

        $this->assertSame(
            [
                'CZK' => 'CZK – de:czk',
                'EUR' => 'EUR – de:eur',
                'GBP' => 'GBP – de:gbp',
                'PLN' => 'PLN – de:pln',
                'HUF' => 'HUF – de:huf',
                'CHF' => 'CHF – de:chf',
                'USD' => 'USD – de:usd',
            ],
            $presenter->options('de'),
        );
    }
}
