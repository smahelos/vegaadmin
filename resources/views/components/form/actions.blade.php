@props([
    'cancelHref',
    'cancelLabel',
    'submitLabel',
    'submitIcon' => 'fa-save',
])

<div class="flex justify-between gap-4 mt-6 mb-20 md:mb-10">
    <a href="{{ $cancelHref }}"
        class="inline-flex items-center justify-center py-4 px-6 md:py-6 md:px-12 border border-white dark:border-gray-700 shadow-xl shadow-indigo-600/30 dark:shadow-gray-900/30 dark:shadow-md text-lg font-semibold rounded-sm text-gray-700 dark:text-white bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors duration-200">
        {{ $cancelLabel }}
    </a>
    <button type="submit" wire:loading.attr="disabled" wire:target="save"
        class="inline-flex items-center justify-center py-4 px-6 md:py-6 md:px-12 border border-transparent rounded-sm dark:shadow-md shadow-xl shadow-indigo-600/30 dark:shadow-gray-900/30 text-lg font-semibold text-white bg-[#490BF4] hover:bg-indigo-500 cursor-pointer disabled:opacity-50 transition-colors duration-200">
        <i class="fas {{ $submitIcon }} mr-2"></i>{{ $submitLabel }}
    </button>
</div>
