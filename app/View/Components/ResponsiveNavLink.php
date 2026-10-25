<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class ResponsiveNavLink extends Component
{
    public function __construct(
        public string $href,
        public bool $active = false,
    ) {}

    /**
     * Render the responsive nav link component
     */
    public function render(): View
    {
        return view('components.responsive-nav-link');
    }
}
