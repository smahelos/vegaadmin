@props([
    'model',
    'label',
    'options',
    'required' => false,
    'placeholder' => null,
    'live' => false,
    'short' => false,
])

{{-- A short select lists the full option labels but shows only the selected value (a code) when closed --}}
<div {{ $attributes->only('class') }}>
    <label for="{{ $model }}" class="block text-base font-medium text-gray-900 dark:text-white mb-2">
        {{ $label }}
        @if ($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    <div @if ($short) x-data class="relative" @endif>
        <select id="{{ $model }}" {{ $attributes->except('class') }} @if ($live) wire:model.live="{{ $model }}" @else wire:model="{{ $model }}" @endif
            @class([
                'form-select block w-full rounded-sm border-blue-100 dark:border-gray-600 focus:border-indigo-600 focus:ring-indigo-600 text-base py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200',
                'pl-3 pr-8 text-transparent dark:text-transparent *:text-gray-900 dark:*:text-gray-200' => $short,
            ])>
            <option value="">{{ $placeholder ?? '—' }}</option>
            @foreach ($options as $value => $text)
                <option value="{{ $value }}">{{ $text }}</option>
            @endforeach
        </select>
        @if ($short)
            <span aria-hidden="true" x-text="$wire.get(@js($model)) || '—'"
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-base text-gray-900 dark:text-gray-200"></span>
        @endif
    </div>
    @error($model)
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
