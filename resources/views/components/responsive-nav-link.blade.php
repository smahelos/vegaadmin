@props(['active'])

@php
$classes = ($active ?? false)
? 'block w-full px-4 py-3 cursor-pointer text-left text-base font-medium text-[#490BF4] hover:text-[#490BF4] dark:text-white dark:hover:text-white
bg-blue-50 hover:bg-indigo-100 dark:bg-[#490BF4] dark:hover:bg-[#490BF4] focus:outline-none focus:text-[#490BF4] focus:bg-indigo-100 dark:focus:bg-[#490BF4] dark:focus:text-white transition duration-150 ease-in-out'
: 'block w-full px-4 py-3 cursor-pointer text-left text-base font-medium text-gray-700 hover:text-[#490BF4] dark:text-gray-400 dark:hover:text-white
hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:text-[#490BF4] focus:bg-gray-50 dark:focus:bg-gray-700 dark:focus:text-white transition duration-150 ease-in-out';
@endphp

<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
