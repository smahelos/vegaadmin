<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Application\Shared\Money\Presenters\CurrencyOptionsPresenter;
use App\Domain\Shared\Money\Enums\Currency;
use Illuminate\Support\Facades\App;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * A select of the currencies an invoice can be issued in.
 */
final class CurrencySelect extends Component
{
    /**
     * @param  array<mixed>|string|null  $selected  usually `old()`, which repeats a failed submit as it came: empty or an array too
     * @param  bool  $codes  codes without names, for the narrow selects of invoice item rows
     */
    public function __construct(
        private readonly CurrencyOptionsPresenter $currencies,
        public readonly string $name,
        public readonly string $label,
        public readonly ?string $id = null,
        public readonly array|string|null $selected = Currency::CZK->value,
        public readonly bool $required = false,
        public readonly string $class = '',
        public readonly string $labelClass = '',
        public readonly string $hint = '',
        public readonly bool $codes = false,
    ) {}

    public function render(): View
    {
        $selectedCurrency = null;
        if (is_string($this->selected)) {
            $selectedCurrency = Currency::tryFrom($this->selected);
        }

        $currencies = array_column(
            Currency::cases(),
            'value',
            'value',
        );
        if (!$this->codes) {
            $currencies = $this->currencies->options(App::getLocale());
        }

        return view(
            'components.currency-select',
            [
                'currencies' => $currencies,
                'selectedCurrency' => $selectedCurrency,
            ],
        );
    }
}
