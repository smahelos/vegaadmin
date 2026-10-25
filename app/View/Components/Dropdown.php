<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class Dropdown extends Component
{
    /**
     * @param  string  $align  Menu alignment (right, left)
     * @param  int  $width  Menu width in pixels
     */
    public function __construct(
        public string $align = 'right',
        public int $width = 48,
        public string $contentClasses = '',
    ) {}

    /**
     * Render the dropdown component
     */
    public function render(): View
    {
        return view('components.dropdown');
    }
}
