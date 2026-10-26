@props([
    'model',
    'label',
    'type' => 'text',
    'required' => false,
    'hint' => null,
])

<div {{ $attributes->only('class') }}>
    <label for="{{ $model }}" class="block text-base font-medium text-gray-900 dark:text-white mb-2">
        {{ $label }}
        @if ($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    <input type="{{ $type }}" id="{{ $model }}" wire:model.blur="{{ $model }}" {{ $attributes->except('class') }}
        class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500">
    @if ($hint)
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif
    @error($model)
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
