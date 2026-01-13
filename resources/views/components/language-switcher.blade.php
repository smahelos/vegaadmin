<div class="language-switcher flex items-center">
    <div class="relative inline-block">
        <select onchange="location = this.value"
            class="appearance-none bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 hover:border-[#490BF4] dark:hover:border-gray-700 rounded-sm py-2 text-sm font-medium text-gray-700 dark:text-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 cursor-pointer">

            @foreach(config('app.available_locales', ['cs', 'en', 'de', 'sk']) as $locale)
            @php
            $currentRoute = \Illuminate\Support\Facades\Route::currentRouteName();
            $routeParams = ['locale' => $locale];

            // Get current route parameters and pass them to the new route
            if (request()->route('slug')) {
            $routeParams['slug'] = request()->route('slug');
            }
            if (request()->route('id')) {
            $routeParams['id'] = request()->route('id');
            }
            if (request()->route('plan')) {
            $routeParams['plan'] = request()->route('plan');
            }
            if (request()->route('categoryId')) {
            $routeParams['categoryId'] = request()->route('categoryId');
            }
            if (request()->route('token')) {
            $routeParams['token'] = request()->route('token');
            }
            @endphp
            <option value="{{ route($currentRoute, $routeParams) }}" {{ app()->getLocale() == $locale ? 'selected' : ''
                }}>
                {{ strtoupper($locale) }}
            </option>
            @endforeach
        </select>
    </div>
</div>
