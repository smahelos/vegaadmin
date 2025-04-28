<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class ApplicationLogo extends Component
{
    /**
     * Render the application logo component
     */
    public function render(): View
    {
        return view('components.application-logo');
    }
}