@extends('layouts.frontend')

@section('title', __('pages.search_results'))

@section('content')

<div class="mb-10 flex justify-between items-center">
    <div class="pt-3">
        <h1 class="main_title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white leading-tight">
            {{ __('pages.search_results') }}
        </h1>
        @if($keyword)
        <p class="text-xl text-gray-600 dark:text-gray-400">
            {{ __('pages.search_for') }}: <strong>"{{ $keyword }}"</strong>
            @if($pages->count() > 0)
            ({{ $pages->count() }} {{ __('pages.results_found') }})
            @endif
        </p>
        @endif
    </div>
    <!-- Search Form -->
    <div class="pt-3">
        <form method="GET" action="{{ route('frontend.pages.search', ['locale' => app()->getLocale()]) }}"
            class="max-w-lg">
            <div class="flex">
                <input type="text" name="q" value="{{ $keyword }}" placeholder="{{ __('pages.search_placeholder') }}"
                    class="flex-1 px-4 py-2 border border-gray-300 dark:border-gray-700 dark:bg-gray-800 rounded-l-sm focus:ring-2 focus:ring-[#490BF4] focus:border-indigo-500"
                    required minlength="3">
                <button type="submit"
                    class="px-6 py-2 bg-[#490BF4] text-white font-semibold rounded-r-sm hover:bg-indigo-500 focus:ring-2 focus:ring-[#490BF4] cursor-pointer transition-colors duration-200">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>
<div
    class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-20 dark:shadow-gray-900/30 dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Search Results -->
        @if($keyword && strlen($keyword) >= 3)
        @if($pages->count() > 0)
        <div class="space-y-6 mb-12">
            @foreach($pages as $page)
            <div
                class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 hover:shadow-md transition duration-200">
                <div class="flex flex-col md:flex-row gap-4">
                    @if($page->main_image_thumb_url)
                    <div class="md:w-48 md:flex-shrink-0">
                        <img src="{{ $page->main_image_thumb_url }}" alt="{{ $page->getName() }}"
                            class="w-full h-32 object-cover rounded-lg">
                    </div>
                    @endif

                    <div class="flex-1">
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                            <a href="{{ route('frontend.pages.show', ['slug' => $page->getSlug(), 'locale' => app()->getLocale()]) }}"
                                class="hover:text-purple-600 dark:hover:text-purple-400">
                                {{ $page->getName() }}
                            </a>
                        </h2>

                        @if($page->getDescription())
                        <p class="text-gray-600 dark:text-gray-400 mb-3">
                            {{ Str::limit($page->getDescription(), 200) }}
                        </p>
                        @endif

                        <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500 dark:text-gray-400 mb-3">
                            @if($page->category)
                            <span class="flex items-center">
                                <i class="fas fa-folder mr-1"></i>
                                <a href="{{ route('frontend.pages.category', ['categoryId' => $page->category->id, 'locale' => app()->getLocale()]) }}"
                                    class="hover:text-purple-600 dark:hover:text-purple-400">
                                    {{ $page->category->getName() }}
                                </a>
                            </span>
                            @endif

                            <span class="flex items-center">
                                <i class="fas fa-calendar mr-1"></i>
                                {{ $page->created_at->format('d.m.Y') }}
                            </span>
                        </div>

                        <a href="{{ route('frontend.pages.show', ['slug' => $page->getSlug(), 'locale' => app()->getLocale()]) }}"
                            class="inline-flex items-center text-purple-600 hover:text-purple-700 dark:text-purple-400 dark:hover:text-purple-300 font-medium">
                            {{ __('pages.read_more') }}
                            <svg class="ml-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
                                    clip-rule="evenodd"></path>
                            </svg>
                        </a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-12">
            <div class="text-gray-400 dark:text-gray-600 mb-4">
                <i class="fas fa-search text-6xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                {{ __('pages.no_results') }}
            </h3>
            <p class="text-gray-600 dark:text-gray-400">
                {{ __('pages.try_different_keywords') }}
            </p>
        </div>
        @endif
        @elseif($keyword && strlen($keyword) < 3) <div class="text-center py-12">
            <div class="text-gray-400 dark:text-gray-600 mb-4">
                <i class="fas fa-exclamation-triangle text-6xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                {{ __('pages.search_too_short') }}
            </h3>
            <p class="text-gray-600 dark:text-gray-400">
                {{ __('pages.search_min_chars') }}
            </p>
    </div>
    @endif

    <!-- Popular Pages -->
    @if($navigationPages && $navigationPages->count() > 0)
    <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
            {{ __('pages.popular_pages') }}
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($navigationPages->take(6) as $navPage)
            <div
                class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 hover:bg-gray-100 dark:hover:bg-gray-600 transition duration-200">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-2">
                    <a href="{{ route('frontend.pages.show', ['slug' => $navPage->getSlug(), 'locale' => app()->getLocale()]) }}"
                        class="hover:text-purple-600 dark:hover:text-purple-400">
                        {{ $navPage->getName() }}
                    </a>
                </h3>
                @if($navPage->getDescription())
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    {{ Str::limit($navPage->getDescription(), 80) }}
                </p>
                @endif
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
</div>

<!-- Back to Homepage -->
<div class="flex flex-wrap gap-4 justify-center mb-10 pt-10 border-t border-gray-200 dark:border-gray-700">
    <a href="{{ route('home', ['locale' => app()->getLocale()]) }}"
        class="inline-flex items-center text-gray-600 dark:text-gray-300 px-4 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 hover:dark:bg-gray-700 font-medium rounded-sm transition-colors duration-200">
        <svg class="mr-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd"
                d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"
                clip-rule="evenodd"></path>
        </svg>
        {{ __('pages.back_to_homepage') }}
    </a>
</div>
@endsection
