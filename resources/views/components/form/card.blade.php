@props(['title' => null])

<div {{ $attributes->merge(['class' => 'bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md dark:shadow-gray-900/30 dark:bg-gray-800']) }}>
    <div class="p-6">
        @if ($title)
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">{{ $title }}</h2>
        @endif
        {{ $slot }}
    </div>
</div>
