<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class Select extends Component
{
    public array $options;
    public string $name;
    public ?string $id;
    public ?string $selected;
    public bool $required;
    public string $label;
    public string $class;
    public string $labelClass;
    public string $valueField;
    public string $textField;
    public string $hint;
    public string $allowsNull;
    public string $placeholder;
    
    /**
     * Create a new component instance
     *
     * @param array $options       Select options array
     * @param string $name         Input element name
     * @param string|null $id      Element ID (uses name if not provided)
     * @param string|null $selected Preselected value
     * @param bool $required       Whether field is required
     * @param string $label        Field label
     * @param string $class        CSS class for select element
     * @param string $labelClass   CSS class for label element
     * @param string $valueField   Key in options array for option value
     * @param string $textField    Key in options array for option text
     * @param string $hint         Help text hint
     * @param string $allowsNull   Whether empty/null option is allowed
     * @param string $placeholder  Placeholder text
     */
    public function __construct(
        array $options,
        string $name, 
        ?string $id = null, 
        ?string $selected = null, 
        bool $required = false,
        string $label = '',
        string $class = '',
        string $labelClass = '',
        string $valueField = 'value',
        string $textField = 'text',
        string $hint = '',
        string $allowsNull = '',
        string $placeholder = ''
    ) {
        $this->options = $options;
        $this->name = $name;
        $this->id = $id ?? $name;
        $this->selected = $selected;
        $this->required = $required;
        $this->label = $label;
        $this->class = $class;
        $this->labelClass = $labelClass;
        $this->valueField = $valueField;
        $this->textField = $textField;
        $this->hint = $hint;
        $this->allowsNull = $allowsNull;
        $this->placeholder = $placeholder;
    }

    /**
     * Render the select component
     */
    public function render(): View
    {
        return view('components.select');
    }
}