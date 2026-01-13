@extends('layouts.frontend')

@section('title', $category->getName() . ' - ' . __('pages.category'))

@section('content')
<div class="bg-white dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- Category Header -->
        <div class="mb-8">
            <nav class="mb-4" aria-label="{{ __('pages.breadcrumbs') }}">
                <ol class="flex items-center space-x-1 text-sm text-gray-500 dark:text-gray-400">
                    <li>
                        <a href="{{ route('home', ['locale' => app()->getLocale()]) }}"
                            class="hover:text-purple-600 dark:hover:text-purple-400">
                            {{ __('pages.homepage') }}
                        </a>
                    </li>
                    <li class="breadcrumb-separator">
                        <span class="text-gray-900 dark:text-white font-medium">
                            {{ $category->getName() }}
                        </span>
                    </li>
                </ol>
            </nav>

            <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
                {{ $category->getName() }}
            </h1>

            @if($category->getDescription())
            <p class="text-xl text-gray-600 dark:text-gray-400">
                {{ $category->getDescription() }}
            </p>
            @endif
        </div>

        <!-- Pages Grid -->
        @if($pages->count() > 0)
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
            @foreach($pages as $page)
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-lg transition duration-200">
                @if($page->main_image_thumb_url)
                <div class="aspect-w-16 aspect-h-9">
                    <img src="{{ $page->main_image_thumb_url }}" alt="{{ $page->getName() }}"
                        class="object-cover rounded-t-lg">
                </div>
                @endif
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">
                        <a href="{{ route('frontend.pages.show', ['slug' => $page->getSlug(), 'locale' => app()->getLocale()]) }}"
                            class="hover:text-purple-600 dark:hover:text-purple-400">
                            {{ $page->getName() }}
                        </a>
                    </h2>

                    @if($page->getDescription())
                    <p class="text-gray-600 dark:text-gray-400 mb-4">
                        {{ Str::limit($page->getDescription(), 120) }}
                    </p>
                    @endif

                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $page->created_at->format('d.m.Y') }}
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
                <i class="fas fa-file-alt text-6xl"></i>
            </div>
            <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">
                {{ __('pages.no_pages_found') }}
            </h3>
            <p class="text-gray-600 dark:text-gray-400">
                {{ __('pages.no_pages_in_category') }}
            </p>
        </div>
        @endif

        <!-- Navigation -->
        @if($navigationPages && $navigationPages->count() > 0)
        <div class="border-t border-gray-200 dark:border-gray-700 pt-8">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                {{ __('pages.other_categories') }}
            </h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($navigationPages as $navPage)
                @if($navPage->category && $navPage->category->id !== $category->id)
                <a href="{{ route('frontend.pages.category', ['categoryId' => $navPage->category->id, 'locale' => app()->getLocale()]) }}"
                    class="text-center p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition duration-200">
                    <div class="text-purple-600 dark:text-purple-400 mb-2">
                        <i class="fas fa-folder text-2xl"></i>
                    </div>
                    <div class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $navPage->category->getName() }}
                    </div>
                </a>
                @endif
                @endforeach
            </div>
        </div>
        @endif

        <!-- Back to Homepage -->
        <div class="text-center mt-8">
            <a href="{{ route('home', ['locale' => app()->getLocale()]) }}"
                class="inline-flex items-center text-purple-600 hover:text-purple-700 dark:text-purple-400 dark:hover:text-purple-300 font-medium">
                <svg class="mr-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z"
                        clip-rule="evenodd"></path>
                </svg>
                {{ __('pages.back_to_homepage') }}
            </a>
        </div>
    </div>
</div>
@endsection
