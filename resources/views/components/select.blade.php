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
        @foreach ($options as $k => $option)
            <option 
                value="{{ is_array($option) ? $option[$valueField] : $k }}" 
                @if($selected !== null && (is_array($option) ? $option[$valueField] : $k) == $selected) selected @endif
            >
                {{ is_array($option) ? $option[$textField] : $option }}
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
