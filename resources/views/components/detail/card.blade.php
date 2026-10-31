@props([
    'title',
    'badge' => null,
    'badgeTone' => 'blue',
])

<div {{ $attributes->merge(['class' => 'bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md dark:shadow-gray-900/30 dark:bg-gray-800 p-6']) }}>
    <div class="border-b border-gray-200 dark:border-gray-700 mb-4 pb-2 flex justify-between items-center">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $title }}</h2>
        @if ($badge)
            <span @class([
                'text-xs px-2 py-1 rounded-full',
                'bg-blue-100 text-blue-800' => $badgeTone === 'blue',
                'bg-green-100 text-green-800' => $badgeTone === 'green',
            ])>{{ $badge }}</span>
        @endif
    </div>
    {{ $slot }}
</div>
