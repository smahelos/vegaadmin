@props(['active', 'href'])

@php
$classes = ($active ?? false)
? 'inline-flex items-center px-2 py-2 text-sm font-semibold text-[#490BF4] dark:text-white hover:text-[#490BF4] dark:hover:text-white focus:outline-none
focus:text-[#490BF4] transition duration-150 ease-in-out'
: 'inline-flex items-center px-2 py-2 text-sm font-semibold text-gray-700 dark:text-gray-400 hover:text-[#490BF4] dark:hover:text-white focus:outline-none
focus:text-[#490BF4] transition duration-150 ease-in-out';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
