<nav x-data="{ open: false }" class="bg-white border-b border-gray-200">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('frontend.dashboard', ['locale' => app()->getLocale()]) }}">
                        <x-application-logo class="block fill-current text-gray-600" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
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
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ml-6">
                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button
                                class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out cursor-pointer">
                                <div>@if(Auth::user()->hasRole('frontend_user_plus'))<span class="text-green-500">[PRO]</span> @endif{{ Auth::user()->name }}</div>

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
                            <a href="{{ route('frontend.profile.edit', ['locale' => app()->getLocale()]) }}" class="block w-full px-4 py-2 text-left text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out">
                                {{ __('general.navigation.profile') }}
                            </a>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('frontend.logout', ['locale' => app()->getLocale()]) }}"
                                class="min-w-50">
                                @csrf
                                <x-dropdown-link :href="route('frontend.logout', ['locale' => app()->getLocale()])" class="cursor-pointer" onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    {{ __('users.actions.logout') }}
                                </x-dropdown-link>

                                <!-- set Locale -->
                                <input type="hidden" name="locale" value="{{ app()->getLocale() }}">
                            </form>
                        </x-slot>
                    </x-dropdown>

                    <div class="relative">
                        <div class="flex justify-end p-2">
                            @include('components.language-switcher')
                        </div>
                    </div>
                @else
                    <div class="space-x-4">
                        <a href="{{ route('frontend.login', ['locale' => app()->getLocale()]) }}" class="text-sm text-gray-700 underline">{{
                            __('users.actions.login') }}</a>
                        <a href="{{ route('frontend.register', ['locale' => app()->getLocale()]) }}" class="text-sm text-gray-700 underline">{{
                            __('users.actions.register') }}</a>
                    </div>

                    <div class="relative">
                        <!-- Add component to switch languages -->
                            <div class="flex justify-end p-2">
                                @include('components.language-switcher')
                        </div>
                    </div>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
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

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
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
            @endauth
        </div>

        <!-- Responsive Settings Options -->
        @auth
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">@if(Auth::user()->hasRole('frontend_user_plus'))<span class="text-green-500">[PRO]</span> @endif{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('frontend.profile.edit', ['locale' => app()->getLocale()])">
                    {{ __('general.navigation.profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('frontend.logout', ['locale' => app()->getLocale()]) }}">
                    @csrf
                    <x-responsive-nav-link :href="route('frontend.logout', ['locale' => app()->getLocale()])" onclick="event.preventDefault();
                                            this.closest('form').submit();">
                        {{ __('users.actions.logout') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
        @else
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('frontend.login', ['locale' => app()->getLocale()])">
                    {{ __('users.actions.login') }}
                </x-responsive-nav-link>

                <x-responsive-nav-link :href="route('frontend.register', ['locale' => app()->getLocale()])">
                    {{ __('users.actions.register') }}
                </x-responsive-nav-link>
            </div>
        </div>
        @endauth
    </div>
</nav>
