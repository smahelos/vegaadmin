@props(['class' => ''])

<button type="button" {{ $attributes->merge(['class' => 'px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md
    text-gray-700 hover:text-gray-700 text-sm font-medium transition-colors cursor-pointer ' . $class]) }}
    onclick="window.history.back()">
    <i class="fas fa-arrow-left mr-2"></i> {{ __('general.actions.back') }}
</button>
