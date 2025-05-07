<div class="language-switcher flex items-center space-x-2">
    @foreach(config('app.available_locales', ['cs', 'en', 'de', 'sk']) as $locale)
        <a href="{{ request()->fullUrlWithQuery(['lang' => $locale]) }}" 
           class="px-2 py-1 rounded-md text-sm {{ app()->getLocale() == $locale ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' }}">
            {{ strtoupper($locale) }}
        </a>
    @endforeach
</div>