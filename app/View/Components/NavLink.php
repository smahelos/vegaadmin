<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class NavLink extends Component
{
    /**
     * Link target URL
     *
     * @var string
     */
    public $href;
    
    /**
     * Whether the link is active
     *
     * @var bool
     */
    public $active;

    /**
     * Create a new component instance
     *
     * @param string $href   Link target URL
     * @param bool $active   Whether link is currently active
     */
    public function __construct($href, $active = false)
    {
        $this->href = $href;
        $this->active = $active;
    }

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
