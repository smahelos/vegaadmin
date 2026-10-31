@props([
    'label',
    'accent' => 'border-green-500',
])

<div {{ $attributes->class(['border-l-4 pl-3 py-1', $accent]) }}>
    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</div>
    <div class="font-medium text-gray-900 dark:text-white">{{ $slot->hasActualContent() ? $slot : '—' }}</div>
</div>
