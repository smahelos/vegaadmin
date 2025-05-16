@props([
    'name',
    'id',
    'label' => null,
    'labelClass' => '',
    'class' => '',
    'selected' => null,
    'options' => [],
    'required' => false,
    'valueField' => 'id',
    'textField' => 'name',
    'hint' => '',
    'allowsNull' => false,
    'placeholder' => '-'
])

<div>
    @if ($label)
            <label for="{{ $id }}" class="block text-base font-medium text-gray-500 mb-2 {{ $labelClass }}">
                {{ $label }}
                @if ($required)
                    <span class="text-red-500">*</span>
                @endif
            </label>
    @endif

    <select 
        name="{{ $name }}" 
        id="{{ $id }}" 
        class="form-select mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 {{ $class }}" 
        data-selected="{{ $selected }}"
        @if($required) required @endif
    >
        @if ($allowsNull)
            <option value="">{{ $placeholder ?? '-' }}</option>
        @endif
        @foreach ($options as $value => $label)
            @php
                if (is_array($label) && isset($label[$valueField])) {
                    $optionValue = $label[$valueField];
                    $optionLabel = $label[$textField] ?? $optionValue;
                } else {
                    $optionValue = $value;
                    $optionLabel = $label;
                }
                
                // Check if this option should be selected
                $isSelected = $selected !== null && (string)$selected === (string)$optionValue;
            @endphp
            
            <option value="{{ $optionValue }}" {{ $isSelected ? 'selected' : '' }}>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>
    @if(isset($hint) && $hint !== '')
        <p class="mt-1 text-sm text-gray-500">
            {{ $hint }}
        </p>
    @endif
    @error($name)
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
    <input type="hidden" name="{{ $name }}_fallback" id="{{ $name }}_fallback" value="{{ $selected }}">
</div>
