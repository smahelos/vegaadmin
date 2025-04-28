<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class DropdownLink extends Component
{
    /**
     * Link target URL
     *
     * @var string
     */
    public $href;

    /**
     * Create a new component instance
     *
     * @param string $href   Link target URL
     */
    public function __construct($href)
    {
        $this->href = $href;
    }

    /**
     * Render the dropdown link component
     */
    public function render(): View
    {
        return view('components.dropdown-link');
    }
}