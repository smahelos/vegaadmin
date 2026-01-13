@props(['name', 'id' => null, 'selected' => null, 'required' => false, 'label' => __('invoices.fields.country'), 'class' => ''])

<div {{ $attributes }}>
    <label for="{{ $id ?? $name }}" class="block text-base font-medium text-gray-900 dark:text-white mb-2">
        {{ $label }} @if($required) <span class="text-red-500">*</span> @endif
    </label>
    <select
        name="{{ $name }}"
        id="{{ $id ?? $name }}"
        @if($required) required @endif
        class="country-select form-select mt-1 block w-full rounded-sm border-blue-100 dark:border-gray-600 focus:border-indigo-600 focus:ring-indigo-600 text-base bg-blue-50 dark:bg-gray-700 dark:text-gray-200 py-2 {{ $class }}"
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
