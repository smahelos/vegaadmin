<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class DropdownLink extends Component
{
    public function __construct(public string $href) {}

    /**
     * Render the dropdown link component
     */
    public function render(): View
    {
        return view('components.dropdown-link', [
            'href' => $this->href,
        ]);
    }
}
