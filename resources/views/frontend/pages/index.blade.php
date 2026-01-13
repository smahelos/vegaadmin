@extends('layouts.frontend')

@section('title', __('pages.all_pages'))

@section('content')
<div class="container mx-auto mb-10">
    {{-- Page Header --}}
    <div class="mb-8">
        <h1 class="main_title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4 leading-tight pt-3">{{
            __('pages.all_pages') }}</h1>
        <p class="text-gray-600">{{ __('pages.browse_all_pages_description') }}</p>
    </div>

    {{-- Search Form --}}
    <div class="mb-8">
        <form action="{{ route('frontend.pages.search', ['locale' => app()->getLocale()]) }}" method="GET"
            class="max-w-md">
            <div class="flex">
                <input type="text" name="q" placeholder="{{ __('pages.search_placeholder') }}"
                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-l-sm focus:ring-2 focus:ring-[#490BF4] focus:border-indigo-500"
                    value="{{ request('q') }}">
                <button type="submit"
                    class="px-6 py-2 bg-[#490BF4] text-white font-semibold rounded-r-sm hover:bg-indigo-500 focus:ring-2 focus:ring-[#490BF4] cursor-pointer transition-colors duration-200">
                    {{ __('pages.search') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Categories Section --}}
    @if($categoriesWithPages->isNotEmpty())
    <div class="mb-12">
        <h2 class="text-2xl font-semibold text-gray-900 mb-6">{{ __('pages.categories') }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($categoriesWithPages as $category)
            <div class="bg-white dark:bg-gray-700 rounded-lg shadow-md p-6 hover:shadow-lg transition-shadow">
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                    <a href="{{ route('frontend.pages.category.slug', ['slug' => $category->getSlug(), 'locale' => app()->getLocale()]) }}"
                        class="hover:text-[#490BF4] dark:hover:text-indigo-300">
                        {{ $category->getName() }}
                    </a>
                </h3>
                @if($category->getDescription())
                <p class="text-gray-600 dark:text-gray-400 mb-4">{{ $category->getDescription() }}</p>
                @endif
                <div class="flex justify-between items-center text-sm text-gray-500">
                    <span>{{ trans_choice('pages.pages_count', $category->published_pages_count, ['count' =>
                        $category->published_pages_count]) }}</span>
                    <a href="{{ route('frontend.pages.category.slug', ['slug' => $category->getSlug(), 'locale' => app()->getLocale()]) }}"
                        class="text-[#490BF4] dark:text-indigo-300 hover:text-blue-800 dark:hover:text-indigo-200">
                        {{ __('pages.view_all') }} →
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- All Pages Section --}}
    <div>
        <h2 class="text-2xl font-semibold text-gray-900 mb-6">{{ __('pages.all_pages') }}</h2>

        @if($pages->isNotEmpty())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($pages as $page)
            <div
                class="bg-white dark:bg-gray-700 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                @if($page->main_image_thumb_url)
                <div class="h-48 bg-gray-200">
                    <img src="{{ $page->main_image_thumb_url }}" alt="{{ $page->getName() }}"
                        class="w-full h-full object-cover">
                </div>
                @endif

                <div class="p-6">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                            {{-- Link to the page --}}
                            <a href="{{ route('frontend.pages.show', ['slug' => $page->getSlug(), 'locale' => app()->getLocale()]) }}"
                                class="hover:text-[#490BF4] dark:hover:text-indigo-300">
                                {{ $page->getName() }}
                            </a>
                        </h3>
                        @if($page->category)
                        <span
                            class="text-xs bg-blue-100 text-blue-800 dark:bg-[#490BF4] dark:text-gray-200 px-2 py-1 rounded-full">
                            {{ $page->category->getName() }}
                        </span>
                        @endif
                    </div>

                    @if($page->getDescription())
                    <p class="text-gray-600 dark:text-gray-400 mb-4 line-clamp-3">{{ $page->getDescription() }}</p>
                    @endif

                    <a href="{{ route('frontend.pages.show', ['slug' => $page->getSlug(), 'locale' => app()->getLocale()]) }}"
                        class="inline-flex items-center text-[#490BF4] dark:text-indigo-300 hover:text-blue-800 dark:hover:text-indigo-200">
                        {{ __('pages.read_more') }}
                        <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7">
                            </path>
                        </svg>
                    </a>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-12">
            <div class="text-gray-400 text-6xl mb-4">📄</div>
            <h3 class="text-xl font-semibold text-gray-900 mb-2">{{ __('pages.no_pages_found') }}</h3>
            <p class="text-gray-600">{{ __('pages.no_pages_description') }}</p>
        </div>
        @endif
    </div>

    {{-- Quick Links --}}
    <div class="mt-10 mb-10 pt-10 border-t border-gray-200 dark:border-gray-700">
        <div class="flex flex-wrap gap-4 justify-center">
            <a href="{{ route('frontend.pages.sitemap', ['locale' => app()->getLocale()]) }}"
                class="px-4 py-2 bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                {{ __('pages.sitemap') }}
            </a>
            <a href="{{ route('frontend.pages.search', ['locale' => app()->getLocale()]) }}"
                class="px-4 py-2 bg-gray-200 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                {{ __('pages.advanced_search') }}
            </a>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>
@endpush
