<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class Dropdown extends Component
{
    /**
     * Dropdown menu alignment
     *
     * @var string
     */
    public $align;

    /**
     * Dropdown menu width (in pixels)
     *
     * @var int
     */
    public $width;

    /**
     * Additional CSS classes for dropdown content
     *
     * @var string
     */
    public $contentClasses;

    /**
     * Create a new component instance
     *
     * @param string $align          Menu alignment (right, left)
     * @param int    $width          Menu width in pixels
     * @param string $contentClasses Additional CSS classes for content 
     */
    public function __construct($align = 'right', $width = 48, $contentClasses = '')
    {
        $this->align = $align;
        $this->width = $width;
        $this->contentClasses = $contentClasses;
    }

    /**
     * Render the dropdown component
     */
    public function render(): View
    {
        return view('components.dropdown');
    }
}