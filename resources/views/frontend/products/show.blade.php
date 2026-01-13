@extends('layouts.frontend')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="main_title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4 leading-tight pt-3"><span
            class="text-base text-gray-900 dark:text-white font-medium">{{ __('products.titles.show') }}</span> {{
        $product->name }}</h1>
    <div class="flex-none space-x-2 space-y-2 grid grid-cols-3 md:flex md:grid-cols-none md:space-x-0">

        <x-back-button />

        <a href="{{ route('frontend.products', ['locale' => app()->getLocale()]) }}"
            title="{{ __('products.actions.back_to_list') }}" aria-label="{{ __('products.actions.back_to_list') }}"
            class="px-4 py-2 col-span-1 md:col-span-1 md:ml-4 inline-block bg-gray-200 dark:bg-gray-800 hover:bg-white dark:hover:bg-gray-600 rounded-sm text-gray-700 dark:text-gray-200 text-sm font-medium transition-colors">
            <i class="fas fa-list"></i>
        </a>
        <a href="{{ route('frontend.product.edit', ['locale' => app()->getLocale(), 'id' => $product->id]) }}"
            title="{{ __('products.actions.edit') }}" aria-label="{{ __('products.actions.edit') }}"
            class="px-4 py-2 col-span-1 md:col-span-1 md:ml-4 inline-block bg-pink-300 dark:bg-pink-500 hover:bg-pink-500 dark:hover:bg-pink-800 rounded-sm text-gray-700 dark:text-gray-200 hover:text-white text-sm font-medium transition-colors">
            <i class="fas fa-pencil-alt"></i>
        </a>
        <span class="hidden"></span>
    </div>
</div>

{{-- UELS Widget --}}
@auth
<div class="mb-6">
    <x-uels-limit-widget entity-type="product" :limit="$limitsData['limit']"
        entity-name="{{ __('uels.entities.product') }}" :show-modal="true" />
</div>
@endauth

<!-- Main product information -->
<div class="mb-6">
    <div class="space-y-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Left column: Basic information -->
            <div
                class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-20 dark:shadow-gray-900/30 dark:bg-gray-800 p-6">
                <div class="border-b border-gray-200 dark:border-gray-700 mb-4 pb-2 flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{
                        __('products.sections.basic_info') }}</h2>
                    <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">{{
                        __('products.tags.product') }}</span>
                </div>

                <div class="space-y-4">
                    <div class="flex flex-wrap">
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-green-500 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('products.fields.name') }}
                                </div>
                                <div class="font-medium dark:text-white">{{ $product->name }}</div>
                            </div>
                        </div>
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-green-500 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('products.fields.slug') }}
                                </div>
                                <div class="font-medium dark:text-white">{{ $product->slug }}</div>
                            </div>
                        </div>
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-orange-500 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('products.fields.price') }}
                                </div>
                                <div class="font-medium dark:text-white">{{$product->price}}</div>
                            </div>
                        </div>
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-orange-500 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{
                                    __('products.fields.category_id') }}</div>
                                <div class="font-medium dark:text-white">{{ $product->category->slug ??
                                    __('general.empty.not_specified') }}</div>
                            </div>
                        </div>
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-yellow-500 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{
                                    __('products.fields.created_at') }}</div>
                                <div class="font-medium dark:text-white">{{
                                    \Carbon\Carbon::parse($product->created_at)->format(\App\Infrastructure\Shared\Support\DateHelper::format())
                                    }}</div>
                            </div>
                        </div>
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-yellow-500 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('products.fields.tax_id') }}
                                </div>
                                <div class="font-medium dark:text-white">
                                    @if($product->tax)
                                    {{ $product->tax['rate'] }}%
                                    @else
                                    {{ __('general.empty.not_specified') }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(!empty($product->description))
                    <div class="bg-gray-20 dark:bg-gray-700 p-3 rounded-sm border border-gray-200 dark:border-gray-700">
                        <div class="text-xs text-gray-500 dark:text-gray-400 mb-1">{{ __('products.fields.description')
                            }}</div>
                        <div class="text-sm whitespace-pre-line dark:text-white">{{ $product->description }}</div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Right column: Additional information -->
            <div
                class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-20 dark:shadow-gray-900/30 dark:bg-gray-800 p-6">
                <div class="border-b border-gray-200 dark:border-gray-700 mb-4 pb-2 flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-900 dark:text-white">{{
                        __('products.sections.detail_info') }}</h2>
                    <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">{{
                        __('products.tags.details') }}</span>
                </div>

                <div class="space-y-4">
                    <div class="flex flex-wrap">
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-orange-500 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{
                                    __('products.fields.is_default') }}</div>
                                <div class="font-medium dark:text-white">
                                    <span
                                        class="font-semibold @if($product->is_default > 0)text-green-600 @else text-red-600 @endif">
                                        @if($product->is_default > 0)
                                        {{ __('general.placeholders.yes') }}
                                        @else
                                        {{ __('general.placeholders.no') }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="w-full md:w-1/2 mb-4">
                            <div class="border-l-4 border-orange-500 pl-3 py-1">
                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ __('products.fields.is_active')
                                    }}</div>
                                <div class="font-medium dark:text-white">
                                    <span
                                        class="font-semibold @if($product->is_active > 0)text-green-600 @else text-red-600 @endif">
                                        @if($product->is_active > 0)
                                        {{ __('general.placeholders.yes') }}
                                        @else
                                        {{ __('general.placeholders.no') }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Display product image if it exists -->
                    @if($product->image)
                    <div class="border-2 border-dashed border-gray-300 dark:border-gray-700 p-3 rounded-sm">
                        <div class="flex justify-center">
                            <img src="{{ Storage::disk('public')->url($product->image) }}" alt="{{ $product->name }}"
                                class="max-h-60 object-contain rounded-md">
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- We could add related invoices/orders here if needed -->

{{-- UELS Modal --}}
@auth
<x-uels-modal />
@endauth

@endsection

{{-- UELS JavaScript Integration --}}
@auth
<x-uels-scripts />
@endauth
