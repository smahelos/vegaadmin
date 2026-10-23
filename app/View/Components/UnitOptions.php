<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Domain\Shared\Unit\Enums\Unit;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * The <option> list of every unit of measure, for a select whose markup stays with its form.
 */
final class UnitOptions extends Component
{
    public function __construct(
        public readonly Unit $selected = Unit::PIECES,
    ) {}

    public function render(): View
    {
        return view('components.unit-options', ['units' => Unit::cases()]);
    }
}
