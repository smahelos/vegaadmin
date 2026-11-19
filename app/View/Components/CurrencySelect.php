<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Domain\Shared\Money\Enums\Currency;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * A select of the currencies an invoice can be issued in.
 */
final class CurrencySelect extends Component
{
    public function __construct(
        public readonly string $name,
        public readonly string $label,
        public readonly ?string $id = null,
        public readonly string $selected = Currency::CZK->value,
        public readonly bool $required = false,
        public readonly string $class = '',
        public readonly string $labelClass = '',
        public readonly string $hint = '',
    ) {}

    public function render(): View
    {
        return view('components.currency-select', ['currencies' => Currency::cases()]);
    }
}
