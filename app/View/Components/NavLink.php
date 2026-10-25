<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class NavLink extends Component
{
    public function __construct(
        public string $href,
        public bool $active = false,
    ) {}

    /**
     * Render the nav link component
     */
    public function render(): View
    {
        return view('components.nav-link', [
            'href' => $this->href,
            'active' => $this->active,
        ]);
    }
}
