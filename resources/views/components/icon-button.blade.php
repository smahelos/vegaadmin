@props([
    'icon',
    'label',
    'tone' => 'gray',
    'href' => null,
])

<{{ $href ? 'a' : 'button' }} @if ($href) href="{{ $href }}" @else type="button" @endif
    title="{{ $label }}" aria-label="{{ $label }}" {{ $attributes->class([
        'px-4 py-2 inline-block rounded-sm text-sm font-medium text-gray-700 dark:text-gray-200 cursor-pointer transition-colors disabled:opacity-50',
        'bg-gray-200 dark:bg-gray-800 hover:bg-white dark:hover:bg-gray-600' => $tone === 'gray',
        'bg-pink-300 dark:bg-pink-500 hover:bg-pink-500 dark:hover:bg-pink-800 hover:text-white' => $tone === 'pink',
        'bg-emerald-200 dark:bg-emerald-500 hover:bg-emerald-500 dark:hover:bg-emerald-700 hover:text-white' => $tone === 'emerald',
        'bg-yellow-200 dark:bg-yellow-500 hover:bg-yellow-500 dark:hover:bg-yellow-700 hover:text-white' => $tone === 'yellow',
        'bg-orange-200 dark:bg-orange-500 hover:bg-orange-400 dark:hover:bg-orange-700 hover:text-white' => $tone === 'orange',
    ]) }}>
    <i class="fas {{ $icon }}"></i>
</{{ $href ? 'a' : 'button' }}>
