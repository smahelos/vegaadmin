@extends('layouts.frontend')

@section('title', __('pages.sitemap'))

@section('content')

<!-- Sitemap Header -->
<div class="mb-6 flex justify-between items-center">
    <h1 class="main_title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4 leading-tight pt-3">
        {{ __('pages.sitemap') }}
    </h1>
</div>

<div
    class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-20 dark:shadow-gray-900/30 dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Pages by Categories -->
        @if($categoriesWithPages && $categoriesWithPages->count() > 0)
        <div class="space-y-12">
            @foreach($categoriesWithPages as $category)
            <div class="border-b border-gray-200 dark:border-gray-700 pb-8 last:border-b-0">
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6 flex items-center">
                    <i class="fas fa-folder text-purple-600 dark:text-purple-400 mr-3"></i>
                    {{ $category->getName() }}
                </h2>

                @if($category->getDescription())
                <p class="text-gray-600 dark:text-gray-400 mb-6">
                    {{ $category->getDescription() }}
                </p>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($category->pages as $page)
                    <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 hover:shadow-md transition duration-200">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            <a href="{{ route('frontend.pages.show', ['slug' => $page->getSlug(), 'locale' => app()->getLocale()]) }}"
                                class="hover:text-purple-600 dark:hover:text-purple-400 flex items-start">
                                <i class="fas fa-file-alt text-purple-600 dark:text-purple-400 mr-2 mt-1 text-sm"></i>
                                {{ $page->getName() }}
                            </a>
                        </h3>

                        @if($page->getDescription())
                        <p class="text-gray-600 dark:text-gray-400 text-sm mb-3">
                            {{ Str::limit($page->getDescription(), 100) }}
                        </p>
                        @endif

                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                            <span>{{ $page->created_at->format('d.m.Y') }}</span>
                            @if($page->children && $page->children->count() > 0)
                            <span
                                class="bg-purple-100 dark:bg-purple-900 text-purple-800 dark:text-purple-200 px-2 py-1 rounded-full">
                                {{ $page->children->count() }} {{ __('pages.subpages_count') }}
                            </span>
                            @endif
                        </div>

                        <!-- Show child pages if any -->
                        @if($page->children && $page->children->count() > 0)
                        <div class="mt-4 pl-4 border-l-2 border-purple-200 dark:border-purple-700">
                            @foreach($page->children as $childPage)
                            @if($childPage->is_currently_published)
                            <div class="mb-2">
                                <a href="{{ route('frontend.pages.show', ['slug' => $childPage->slug, 'locale' => app()->getLocale()]) }}"
                                    class="text-sm text-gray-600 dark:text-gray-400 hover:text-purple-600 dark:hover:text-purple-400 flex items-center">
                                    <i class="fas fa-chevron-right mr-2 text-xs"></i>
                                    {{ $childPage->name }}
                                </a>
                            </div>
                            @endif
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
        @else
        <div class="text-center py-12">
            <div class="text-gray-400 dark:text-gray-600 mb-4">
                <i class="fas fa-sitemap text-6xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                {{ __('pages.no_pages_found') }}
            </h3>
            <p class="text-gray-600 dark:text-gray-400">
                {{ __('pages.no_published_pages') }}
            </p>
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
