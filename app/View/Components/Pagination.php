<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;
use Illuminate\Pagination\LengthAwarePaginator;

class Pagination extends Component
{
    /**
     * Paginator instance
     *
     * @var \Illuminate\Pagination\LengthAwarePaginator
     */
    public $paginator;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct(LengthAwarePaginator $paginator)
    {
        $this->paginator = $paginator;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.pagination');
    }
}
