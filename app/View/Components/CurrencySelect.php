<?php

namespace App\View\Components;

use App\Services\CurrencyService;
use Illuminate\View\Component;
use Illuminate\View\View;

class CurrencySelect extends Component
{
    public array $currencies;
    public string $name;
    public ?string $id;
    public ?string $selected;
    public bool $required;
    public string $label;
    public string $class;
    public string $labelClass;
    public string $hint;
    
    /**
     * Create a new component instance
     *
     * @param CurrencyService $currencyService Service for retrieving available currencies
     * @param string $name         Input element name
     * @param string|null $id      Element ID (uses name if not provided)
     * @param string|null $selected Preselected value
     * @param bool $required       Whether field is required
     * @param string $label        Field label
     * @param string $class        CSS class for select element
     * @param string $labelClass   CSS class for label element
     * @param string $hint         Help text hint
     */
    public function __construct(
        CurrencyService $currencyService,
        string $name, 
        ?string $id = null, 
        ?string $selected = null, 
        bool $required = false,
        string $label = 'Currency',
        string $class = '',
        string $labelClass = '',
        string $hint = ''
    ) {
        $this->name = $name;
        $this->id = $id ?? $name;
        $this->selected = $selected ?? 'CZK';
        $this->required = $required;
        $this->label = $label;
        $this->class = $class;
        $this->labelClass = $labelClass;
        $this->hint = $hint;
        
        // Load common currencies
        $this->currencies = $currencyService->getCommonCurrencies();
    }

    /**
     * Render the currency select component
     */
    public function render(): View
    {
        return view('components.currency-select');
    }
}