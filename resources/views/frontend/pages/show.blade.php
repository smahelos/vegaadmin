@extends('layouts.frontend')

@section('title', $page->getName())

@push('after_styles')
<style>
    .content-section img {
        max-width: 100%;
        height: auto;
        border-radius: 0.5rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .breadcrumb-separator::before {
        content: "›";
        margin: 0 0.5rem;
        color: #9CA3AF;
    }
</style>
@endpush

@section('content')


<!-- Breadcrumbs -->
@if(count($breadcrumbs) > 0)
<nav class="mb-8" aria-label="{{ __('pages.breadcrumbs') }}">
    <ol class="flex items-center space-x-1 text-sm text-gray-500 dark:text-gray-400">
        <li>
            <a href="{{ route('home', ['locale' => app()->getLocale()]) }}"
                class="hover:text-purple-600 dark:hover:text-purple-400">
                {{ __('pages.homepage') }}
            </a>
        </li>
        @foreach($breadcrumbs as $breadcrumb)
        <li class="breadcrumb-separator">
            @if($loop->last)
            <span class="text-gray-900 dark:text-white font-medium">
                {{ $breadcrumb['name'] }}
            </span>
            @else
            <a href="{{ route('frontend.pages.show', ['slug' => $breadcrumb['slug'], 'locale' => app()->getLocale()]) }}"
                class="hover:text-purple-600 dark:hover:text-purple-400">
                {{ $breadcrumb['name'] }}
            </a>
            @endif
        </li>
        @endforeach
    </ol>
</nav>
@endif

<!-- Page Header -->
<div class="mb-8">
    <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
        {{ $page->getName() }}
    </h1>

    @if($page->getDescription())
    <p class="text-xl text-gray-600 dark:text-gray-400">
        {{ $page->getDescription() }}
    </p>
    @endif

    <!-- Page Meta -->
    <div class="flex flex-wrap items-center gap-4 mt-4 text-sm text-gray-500 dark:text-gray-400">
        @if($page->category)
        <span class="flex items-center">
            <i class="fas fa-folder mr-1"></i>
            <a href="{{ route('frontend.pages.category', ['categoryId' => $page->category->id, 'locale' => app()->getLocale()]) }}"
                class="hover:text-purple-600 dark:hover:text-purple-400">
                {{ $page->category->getName() }}
            </a>
        </span>
        @endif

        @if($page->publishedBy)
        <span class="flex items-center">
            <i class="fas fa-user mr-1"></i>
            {{ $page->publishedBy->name }}
        </span>
        @endif

        <span class="flex items-center">
            <i class="fas fa-calendar mr-1"></i>
            {{ $page->created_at->format('d.m.Y') }}
        </span>
    </div>
</div>

<div
    class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-20 dark:shadow-gray-900/30 dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 text-gray-600 dark:text-gray-400">

        <!-- Main Image -->
        @if($page->main_image_url)
        <div class="mb-12">
            <img src="{{ $page->main_image_url }}" alt="{{ $page->getName() }}"
                class="w-full max-w-4xl mx-auto rounded-lg shadow-lg">
        </div>
        @endif

        <!-- Page Content -->
        @if($page->content)
        @php
        $content = $page->content[app()->getLocale()] ?? '';
        @endphp
        @if($content)
        <div class="content-section prose prose-purple max-w-none dark:prose-invert mb-12">
            {!! $content !!}
        </div>
        @endif
        @endif

        <!-- Image Gallery -->
        @if($page->images && count($page->image_urls) > 0)
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                {{ __('pages.gallery') }}
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($page->image_urls as $imageUrl)
                <div class="aspect-w-16 aspect-h-9">
                    <img src="{{ $imageUrl }}" alt="{{ $page->getName() }}"
                        class="object-cover rounded-lg shadow-md hover:shadow-lg transition duration-200 cursor-pointer"
                        onclick="openLightbox('{{ $imageUrl }}')">
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Child Pages -->
        @if($page->children && $page->children->count() > 0)
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                {{ __('pages.subpages') }}
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($page->children as $childPage)
                @if($childPage->is_currently_published)
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6 hover:shadow-md transition duration-200">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                        <a href="{{ route('frontend.pages.show', ['slug' => $childPage->getSlug(), 'locale' => app()->getLocale()]) }}"
                            class="hover:text-purple-600 dark:hover:text-purple-400">
                            {{ $childPage->getName() }}
                        </a>
                    </h3>
                    @if($childPage->getDescription())
                    <p class="text-gray-600 dark:text-gray-400 mb-3">
                        {{ Str::limit($childPage->getDescription(), 150) }}
                    </p>
                    @endif
                    <a href="{{ route('frontend.pages.show', ['slug' => $childPage->getSlug(), 'locale' => app()->getLocale()]) }}"
                        class="inline-flex items-center text-purple-600 hover:text-purple-700 dark:text-purple-400 dark:hover:text-purple-300 font-medium">
                        {{ __('pages.read_more') }}
                        <svg class="ml-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z"
                                clip-rule="evenodd"></path>
                        </svg>
                    </a>
                </div>
                @endif
                @endforeach
            </div>
        </div>
        @endif

        <!-- Related Pages -->
        @if($relatedPages && $relatedPages->count() > 0)
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
                {{ __('pages.related_pages') }}
            </h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                @foreach($relatedPages as $relatedPage)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md hover:shadow-lg transition duration-200">
                    @if($relatedPage->main_image_thumb_url)
                    <div class="aspect-w-16 aspect-h-9">
                        <img src="{{ $relatedPage->main_image_thumb_url }}" alt="{{ $relatedPage->getName() }}"
                            class="object-cover rounded-t-lg">
                    </div>
                    @endif
                    <div class="p-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                            <a href="{{ route('frontend.pages.show', ['slug' => $relatedPage->getSlug(), 'locale' => app()->getLocale()]) }}"
                                class="hover:text-purple-600 dark:hover:text-purple-400">
                                {{ $relatedPage->getName() }}
                            </a>
                        </h3>
                        @if($relatedPage->getDescription())
                        <p class="text-gray-600 dark:text-gray-400 text-sm">
                            {{ Str::limit($relatedPage->getDescription(), 100) }}
                        </p>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Back to Homepage -->
        <div class="text-center">
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

<!-- Lightbox Modal for Images -->
<div id="lightbox" class="fixed inset-0 bg-black bg-opacity-75 hidden z-50 flex items-center justify-center p-4"
    onclick="closeLightbox()">
    <div class="max-w-full max-h-full">
        <img id="lightbox-image" src="" alt="" class="max-w-full max-h-full object-contain">
    </div>
    <button onclick="closeLightbox()" class="absolute top-4 right-4 text-white text-3xl hover:text-gray-300">
        &times;
    </button>
</div>

@push('after_scripts')
<script>
    function openLightbox(imageUrl) {
    document.getElementById('lightbox-image').src = imageUrl;
    document.getElementById('lightbox').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeLightbox() {
    document.getElementById('lightbox').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

// Close lightbox on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeLightbox();
    }
});
</script>
@endpush
@endsection
