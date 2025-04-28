@props(['name', 'id' => null, 'selected' => null, 'required' => false, 'label' => 'Země', 'class' => ''])

<div {{ $attributes }}>
    <label for="{{ $id ?? $name }}" class="block text-base font-medium text-gray-500 mb-2">
        {{ $label }} @if($required) <span class="text-red-500">*</span> @endif
    </label>
    <select 
        name="{{ $name }}" 
        id="{{ $id ?? $name }}" 
        @if($required) required @endif
        class="country-select form-select mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-[#FDFDFC] {{ $class }}"
        data-selected="{{ $selected }}">
        <option value="">{{ __('general.placeholders.select_country') }}</option>
    </select>
    @if(isset($hint) && $hint !== '')
        <p class="mt-1 text-sm text-gray-500">
            {{ $hint }}
        </p>
    @endif
    @error($name)
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>