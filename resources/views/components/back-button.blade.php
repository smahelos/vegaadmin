@props(['class' => ''])

<button type="button" {{ $attributes->merge(['class' => 'px-4 py-2 bg-white hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-500 rounded-sm
    text-gray-700 hover:text-gray-700 dark:text-gray-200 dark:hover:text-gray-200 text-sm font-medium transition-colors cursor-pointer ' . $class]) }}
    title="{{ __('general.actions.back') }}"
    aria-label="{{ __('general.actions.back') }}"
    onclick="window.history.back()">
    <i class="fas fa-arrow-left"></i>
</button>
