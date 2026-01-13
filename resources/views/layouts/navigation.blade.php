<nav x-data="{ open: false }"
    class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700 sticky top-0 z-50">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-20">
            <div class="flex items-center">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('home', ['locale' => app()->getLocale()]) }}" class="flex items-center">
                        <x-application-logo class="h-8 w-auto" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden lg:flex lg:items-center lg:ml-10 lg:space-x-8">
                    @auth
                    <x-nav-link :href="route('frontend.dashboard', ['locale' => app()->getLocale()])"
                        :active="request()->routeIs('frontend.dashboard')">
                        {{ __('general.navigation.dashboard') }}
                    </x-nav-link>

                    <x-nav-link :href="route('frontend.invoices', ['locale' => app()->getLocale()])"
                        :active="request()->routeIs('frontend.invoice*')">
                        {{ __('general.navigation.invoices') }}
                    </x-nav-link>

                    @if(Auth::user()->hasPermissionTo('frontend.can_create_edit_client'))
                    <x-nav-link :href="route('frontend.clients', ['locale' => app()->getLocale()])"
                        :active="request()->routeIs('frontend.client*')">
                        {{ __('general.navigation.clients') }}
                    </x-nav-link>
                    @endif

                    @if(Auth::user()->hasPermissionTo('frontend.can_create_edit_supplier'))
                    <x-nav-link :href="route('frontend.suppliers', ['locale' => app()->getLocale()])"
                        :active="request()->routeIs('frontend.supplier*')">
                        {{ __('general.navigation.suppliers') }}
                    </x-nav-link>
                    @endif

                    @if(Auth::user()->hasPermissionTo('frontend.can_create_edit_product'))
                    <x-nav-link :href="route('frontend.products', ['locale' => app()->getLocale()])"
                        :active="request()->routeIs('frontend.product*')">
                        {{ __('general.navigation.products') }}
                    </x-nav-link>
                    @endif
                    @endauth

                    {{-- <!-- Pages navigation (for all users) -->
                    <x-nav-link :href="route('frontend.pages.index', ['locale' => app()->getLocale()])"
                        :active="request()->routeIs('frontend.pages*')">
                        {{ __('general.navigation.pages') }}
                    </x-nav-link> --}}

                    @if(!Auth::user())
                    <!-- Public pages link -->
                    <x-nav-link :href="route('frontend.invoice.create.guest', ['locale' => app()->getLocale()])"
                        :active="request()->routeIs('frontend.invoice*')">
                        {{ __('general.navigation.create_invoice') }}
                    </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden lg:flex lg:items-center lg:space-x-4">
                <!-- Theme Toggle -->
                <button id="theme-toggle" type="button"
                    class="text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:ring-4 focus:ring-gray-200 dark:focus:ring-gray-700 rounded-lg text-sm p-2.5 transition-colors duration-200 cursor-pointer">
                    <svg id="theme-toggle-dark-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.293 13.293A8 8 0 016.707 2.707a8.001 8.001 0 1010.586 10.586z"></path>
                    </svg>
                    <svg id="theme-toggle-light-icon" class="hidden w-5 h-5" fill="currentColor" viewBox="0 0 20 20"
                        xmlns="http://www.w3.org/2000/svg">
                        <path
                            d="M10 2a1 1 0 011 1v1a1 1 0 11-2 0V3a1 1 0 011-1zm4 8a4 4 0 11-8 0 4 4 0 018 0zm-.464 4.95l.707.707a1 1 0 001.414-1.414l-.707-.707a1 1 0 00-1.414 1.414zm2.12-10.607a1 1 0 010 1.414l-.706.707a1 1 0 11-1.414-1.414l.707-.707a1 1 0 011.414 0zM17 11a1 1 0 100-2h-1a1 1 0 100 2h1zm-7 4a1 1 0 011 1v1a1 1 0 11-2 0v-1a1 1 0 011-1zM5.05 6.464A1 1 0 106.465 5.05l-.708-.707a1 1 0 00-1.414 1.414l.707.707zm1.414 8.486l-.707.707a1 1 0 01-1.414-1.414l.707-.707a1 1 0 011.414 1.414zM4 11a1 1 0 100-2H3a1 1 0 000 2h1z"
                            fill-rule="evenodd" clip-rule="evenodd"></path>
                    </svg>
                </button>

                <!-- Language Switcher -->
                <div class="relative">
                    @include('components.language-switcher')
                </div>

                @auth
                <!-- User Dropdown -->
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="flex items-center px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-300 focus:outline-none focus:text-gray-900 dark:focus:text-white transition duration-150 ease-in-out cursor-pointer">
                            <div class="flex items-center">
                                @if(Auth::user()->hasRole('frontend_user_plus'))
                                <span class="text-indigo-600 font-semibold text-xs mr-2">PRO</span>
                                @endif
                                <span>{{ Auth::user()->name }}</span>
                            </div>
                            <div class="ml-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <!-- Profile Link -->
                        <a href="{{ route('frontend.profile.edit', ['locale' => app()->getLocale()]) }}"
                            class="block w-full px-4 py-2 text-left text-sm leading-5 rounded-t-sm border-b border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                            {{ __('general.navigation.profile') }}
                        </a>

                        <!-- My Subscription Link -->
                        <a href="{{ route('subscriptions.my-subscription', ['locale' => app()->getLocale()]) }}"
                            class="block w-full px-4 py-2 text-left text-sm leading-5 rounded-t-sm border-b border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                            {{ __('subscription.my_subscription') }}
                        </a>

                        <!-- Subscriptions Link -->
                        <a href="{{ route('subscriptions.index', ['locale' => app()->getLocale()]) }}"
                            class="block w-full px-4 py-2 text-left text-sm leading-5 rounded-t-sm border-b border-gray-200 dark:border-gray-700 text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                            {{ __('subscription.subscriptions') }}
                        </a>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('frontend.logout', ['locale' => app()->getLocale()]) }}"
                            class="min-w-50">
                            @csrf
                            <x-dropdown-link :href="route('frontend.logout', ['locale' => app()->getLocale()])"
                                class="cursor-pointer rounded-b-sm"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('users.actions.logout') }}
                            </x-dropdown-link>
                            <input type="hidden" name="locale" value="{{ app()->getLocale() }}">
                        </form>
                    </x-slot>
                </x-dropdown>
                @else
                <!-- Auth Links -->
                <div class="flex items-center space-x-4">
                    <a href="{{ route('frontend.login', ['locale' => app()->getLocale()]) }}"
                        class="text-sm font-semibold text-gray-700 dark:text-gray-400 hover:text-[#490BF4] dark:hover:text-white transition-colors duration-200 px-3 py-2 rounded-sm border border-gray-300 dark:border-gray-700 hover:border-[#490BF4] dark:hover:border-gray-600">
                        {{ __('users.actions.login') }}
                    </a>
                    <a href="{{ route('frontend.register', ['locale' => app()->getLocale()]) }}"
                        class="text-sm font-semibold text-white hover:text-[#490BF4] bg-[#490BF4] hover:bg-gray-100 dark:hover:bg-gray-400 transition-colors duration-200 px-4 py-2 rounded-sm">
                        {{ __('users.actions.register') }}
                    </a>
                </div>
                @endauth
            </div>

            <!-- Mobile menu button -->
            <div class="flex items-center lg:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-sm text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700 border-gray-100 hover:border-gray-200 dark:hover:border-gray-600 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-700 focus:text-gray-500 cursor-pointer transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}"
        class="hidden lg:hidden border-t border-gray-100 dark:border-gray-700">
        <div class="pt-1 pb-1 space-y-1">
            @auth
            <x-responsive-nav-link :href="route('frontend.dashboard', ['locale' => app()->getLocale()])"
                :active="request()->routeIs('frontend.dashboard')">
                {{ __('general.navigation.dashboard') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('frontend.invoices', ['locale' => app()->getLocale()])"
                :active="request()->routeIs('frontend.invoice*')">
                {{ __('general.navigation.invoices') }}
            </x-responsive-nav-link>

            @if(Auth::user()->hasPermissionTo('frontend.can_create_edit_client'))
            <x-responsive-nav-link :href="route('frontend.clients', ['locale' => app()->getLocale()])"
                :active="request()->routeIs('frontend.client*')">
                {{ __('general.navigation.clients') }}
            </x-responsive-nav-link>
            @endif

            @if(Auth::user()->hasPermissionTo('frontend.can_create_edit_supplier'))
            <x-responsive-nav-link :href="route('frontend.suppliers', ['locale' => app()->getLocale()])"
                :active="request()->routeIs('frontend.supplier*')">
                {{ __('general.navigation.suppliers') }}
            </x-responsive-nav-link>
            @endif

            @if(Auth::user()->hasPermissionTo('frontend.can_create_edit_product'))
            <x-responsive-nav-link :href="route('frontend.products', ['locale' => app()->getLocale()])"
                :active="request()->routeIs('frontend.product*')">
                {{ __('general.navigation.products') }}
            </x-responsive-nav-link>
            @endif

            <!-- Subscriptions navigation (mobile) -->
            <x-responsive-nav-link :href="route('subscriptions.index', ['locale' => app()->getLocale()])"
                :active="request()->routeIs('subscriptions*')">
                {{ __('subscription.subscriptions') }}
            </x-responsive-nav-link>

            <x-responsive-nav-link :href="route('frontend.invoice.create.guest', ['locale' => app()->getLocale()])"
                :active="request()->routeIs('frontend.invoice*')">
                {{ __('general.navigation.create_invoice') }}
            </x-responsive-nav-link>
            @else
            <x-responsive-nav-link :href="route('frontend.invoice.create.guest', ['locale' => app()->getLocale()])"
                :active="request()->routeIs('frontend.invoice*')">
                {{ __('general.navigation.create_invoice') }}
            </x-responsive-nav-link>
            @endauth
        </div>

        <!-- Mobile User Menu -->
        @auth
        <div class="pt-1 pb-1 border-t border-gray-100 dark:border-gray-700">
            <div class="px-4 border-b-1 border-dashed border-gray-200 dark:border-gray-600 mb-2 pb-2">
                <div class="font-medium text-base text-gray-800 dark:text-gray-300 flex items-center">
                    @if(Auth::user()->hasRole('frontend_user_plus'))
                    <span class="text-indigo-600 dark:text-indigo-400 font-semibold text-xs mr-2">PRO</span>
                    @endif
                    {{ Auth::user()->name }}
                </div>
                <div class="font-medium text-sm text-gray-500 dark:text-gray-400">{{ Auth::user()->email }}</div>
            </div>

            <div class="space-y-1">
                <x-responsive-nav-link :href="route('frontend.profile.edit', ['locale' => app()->getLocale()])">
                    {{ __('general.navigation.profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('frontend.logout', ['locale' => app()->getLocale()]) }}">
                    @csrf
                    <x-responsive-nav-link :href="route('frontend.logout', ['locale' => app()->getLocale()])"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('users.actions.logout') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
        @else
        <div class="pt-1 pb-1 border-t border-gray-100 dark:border-gray-700">
            <div class="space-y-1">
                <x-responsive-nav-link :href="route('frontend.login', ['locale' => app()->getLocale()])">
                    {{ __('users.actions.login') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('frontend.register', ['locale' => app()->getLocale()])">
                    {{ __('users.actions.register') }}
                </x-responsive-nav-link>
            </div>
        </div>
        @endauth

        <!-- Mobile Language Switcher -->
        @include('components.language-switcher-mobile')
    </div>
</nav>
