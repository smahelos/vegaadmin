<div class="pt-4 pb-4 border-t border-gray-100 dark:border-gray-700">
    <div class="px-4">
        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-2">{{ __('general.navigation.language') }}</div>
        <div class="flex space-x-2">
            @foreach(config('app.available_locales', ['cs', 'en', 'de', 'sk']) as $locale)
            @php
            $currentRoute = \Illuminate\Support\Facades\Route::currentRouteName();
            $routeParams = array_merge(request()->route()?->parameters() ?? [], ['locale' => $locale]);
            @endphp
            <a href="{{ route($currentRoute, $routeParams) }}"
                class="px-3 py-1 font-semibold text-sm rounded-sm {{ app()->getLocale() == $locale ? 'bg-indigo-100 dark:bg-[#490BF4] hover:bg-indigo-400 text-indigo-700 dark:text-white' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white' }}">
                {{ strtoupper($locale) }}
            </a>
            @endforeach
        </div>
    </div>
</div>
