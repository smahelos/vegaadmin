@props([
    'model',
    'label',
    'options',
    'required' => false,
    'placeholder' => null,
    'live' => false,
])

<div {{ $attributes->only('class') }}>
    <label for="{{ $model }}" class="block text-base font-medium text-gray-900 dark:text-white mb-2">
        {{ $label }}
        @if ($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    <select id="{{ $model }}" @if ($live) wire:model.live="{{ $model }}" @else wire:model="{{ $model }}" @endif
        class="form-select block w-full rounded-sm border-blue-100 dark:border-gray-600 focus:border-indigo-600 focus:ring-indigo-600 text-base py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200">
        <option value="">{{ $placeholder ?? '—' }}</option>
        @foreach ($options as $value => $text)
            <option value="{{ $value }}">{{ $text }}</option>
        @endforeach
    </select>
    @error($model)
        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>
