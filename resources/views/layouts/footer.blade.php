{{-- Footer Component --}}
<footer class="bg-gray-900 text-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">

            {{-- Company Info --}}
            <div class="col-span-1 lg:col-span-2">
                <div class="flex items-center mb-4">
                    <h3 class="text-2xl font-bold text-white">{{ config('app.name', 'Billex') }}</h3>
                </div>
                <p class="text-gray-300 mb-6 max-w-md">
                    {{ __('footer.description') }}
                </p>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">
                        <span class="sr-only">Facebook</span>
                        <i class="fab fa-facebook-f w-5 h-5"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">
                        <span class="sr-only">Twitter</span>
                        <i class="fab fa-twitter w-5 h-5"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">
                        <span class="sr-only">LinkedIn</span>
                        <i class="fab fa-linkedin-in w-5 h-5"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors duration-200">
                        <span class="sr-only">Instagram</span>
                        <i class="fab fa-instagram w-5 h-5"></i>
                    </a>
                </div>
            </div>

            {{-- Product Links --}}
            <div>
                <h4 class="text-lg font-semibold text-white mb-4">{{ __('footer.product') }}</h4>
                <ul class="space-y-3">
                    <li>
                        <a href="{{ route('frontend.pages.show', ['locale' => app()->getLocale(), 'slug' => 'funkce']) }}"
                            class="text-gray-300 hover:text-white transition-colors duration-200">
                            {{ __('footer.features') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('frontend.pages.show', ['locale' => app()->getLocale(), 'slug' => 'cenik']) }}" class="text-gray-300 hover:text-white transition-colors duration-200">
                            {{ __('footer.pricing') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('frontend.pages.show', ['locale' => app()->getLocale(), 'slug' => 'sablony']) }}" class="text-gray-300 hover:text-white transition-colors duration-200">
                            {{ __('footer.templates') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('frontend.pages.show', ['locale' => app()->getLocale(), 'slug' => 'api']) }}" class="text-gray-300 hover:text-white transition-colors duration-200">
                            {{ __('footer.api') }}
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Support Links --}}
            <div>
                <h4 class="text-lg font-semibold text-white mb-4">{{ __('footer.support') }}</h4>
                <ul class="space-y-3">
                    <li>
                        <a href="{{ route('frontend.pages.show', ['locale' => app()->getLocale(), 'slug' => 'napoveda']) }}" class="text-gray-300 hover:text-white transition-colors duration-200">
                            {{ __('footer.help_center') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('frontend.pages.show', ['locale' => app()->getLocale(), 'slug' => 'kontakt']) }}" class="text-gray-300 hover:text-white transition-colors duration-200">
                            {{ __('footer.contact') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('frontend.pages.show', ['locale' => app()->getLocale(), 'slug' => 'dokumentace']) }}" class="text-gray-300 hover:text-white transition-colors duration-200">
                            {{ __('footer.documentation') }}
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('frontend.pages.show', ['locale' => app()->getLocale(), 'slug' => 'stav-sluzby']) }}" class="text-gray-300 hover:text-white transition-colors duration-200">
                            {{ __('footer.status') }}
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Newsletter Signup --}}
        <div class="mt-12 pt-8 border-t border-gray-800">
            <div
                class="max-w-md mx-auto text-center lg:max-w-none lg:text-left lg:flex lg:items-center lg:justify-between">
                <div class="lg:flex-1">
                    <h4 class="text-lg font-semibold text-white mb-2">{{ __('footer.newsletter_title') }}</h4>
                    <p class="text-gray-300">{{ __('footer.newsletter_subtitle') }}</p>
                </div>
                <div class="mt-6 lg:mt-0 lg:ml-8">
                    <form class="flex flex-col sm:flex-row gap-3">
                        <input type="email" placeholder="{{ __('footer.email_placeholder') }}"
                            class="flex-1 px-4 py-2 bg-gray-800 border border-gray-700 rounded-sm text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <button type="submit"
                            class="px-6 py-2 bg-[#490BF4] hover:bg-indigo-500 text-white font-semibold rounded-sm transition-colors duration-200 cursor-pointer">
                            {{ __('footer.subscribe') }}
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Bottom Bar --}}
        <div class="mt-12 pt-8 border-t border-gray-800 flex flex-col md:flex-row justify-between items-center">
            <div class="text-gray-400 text-sm">
                <p>&copy; {{ date('Y') }} {{ config('app.name', 'Billex') }}. {{ __('footer.all_rights_reserved') }}</p>
            </div>
            <div class="flex space-x-6 mt-4 md:mt-0">
                <a href="{{ route('frontend.pages.show', ['locale' => app()->getLocale(), 'slug' => 'zasady-ochrany-osobnich-udaju']) }}" class="text-gray-400 hover:text-white text-sm transition-colors duration-200">
                    {{ __('footer.privacy_policy') }}
                </a>
                <a href="{{ route('frontend.pages.show', ['locale' => app()->getLocale(), 'slug' => 'obchodni-podminky']) }}" class="text-gray-400 hover:text-white text-sm transition-colors duration-200">
                    {{ __('footer.terms_of_service') }}
                </a>
                <a href="{{ route('frontend.pages.show', ['locale' => app()->getLocale(), 'slug' => 'zasady-pouzivani-cookies']) }}" class="text-gray-400 hover:text-white text-sm transition-colors duration-200">
                    {{ __('footer.cookies') }}
                </a>
            </div>
        </div>
    </div>
</footer>
