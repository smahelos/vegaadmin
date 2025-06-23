<div class="language-switcher flex items-center space-x-2">
    @foreach(config('app.available_locales', ['cs', 'en', 'de', 'sk']) as $locale)
        <x-nav-link :href="route(\Illuminate\Support\Facades\Route::currentRouteName(), ['locale' => $locale, 'id' => request()->route('id')])" :active="app()->getLocale() == $locale">
            {{ strtoupper($locale) }}
        </x-nav-link>
    @endforeach
</div>
