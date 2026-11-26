@props([
    'model',
    'label',
    'hint' => null,
])

<div {{ $attributes->only('class') }}>
    <label class="flex items-center gap-2 text-base text-gray-900 dark:text-white">
        <input type="checkbox" id="{{ $model }}" wire:model="{{ $model }}"
            class="rounded-sm border-blue-100 text-indigo-600 focus:ring-indigo-600">
        {{ $label }}
    </label>
    @if ($hint)
        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $hint }}</p>
    @endif
    @error($model)
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
