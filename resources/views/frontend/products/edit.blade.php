@extends('layouts.frontend')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h1 class="main_title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4 leading-tight pt-3">{{
        __('products.actions.edit') }}</h1>
    <div class="flex-none space-x-2 space-y-2 grid grid-cols-2 md:flex md:grid-cols-none md:space-x-0">

        <x-back-button />

        <a href="{{ route('frontend.products', ['locale' => app()->getLocale()]) }}"
            title="{{ __('products.actions.back_to_list') }}" aria-label="{{ __('products.actions.back_to_list') }}"
            class="px-4 py-2 col-span-1 md:col-span-1 md:ml-4 inline-block bg-gray-200 dark:bg-gray-800 hover:bg-white dark:hover:bg-gray-600 rounded-sm text-gray-700 dark:text-gray-200 text-sm font-medium transition-colors">
            <i class="fas fa-list"></i>
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

<form action="{{ route('frontend.product.update', ['locale' => app()->getLocale(), 'id' => $product->id]) }}"
    method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div
            class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-20 dark:shadow-gray-900/30 dark:bg-gray-800">
            <div class="p-6">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">{{
                    __('products.sections.basic_info') }}</h2>

                <!-- Left column -->
                @php
                $leftColumnFields = ['name', 'slug', 'category_id', 'tax_id', 'price', 'currency', 'supplier_id'];
                @endphp
                @foreach($fields as $field)
                @if ($field['name'] === 'name' || $field['name'] === 'price')
                <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
                    @elseif ($field['name'] === 'category_id')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @endif

                        @if ($field['name'] === 'category_id')
                        <!-- Product Category -->
                        <div class="mb-5">
                            <x-select name="{{ $field['name'] }}" label="{{ $field['label'] }}"
                                id="{{ $field['name'] }}" valueField="id"
                                :selected="old($field['name'], $product->tax_id ?? '')"
                                required="{{ isset($field['required']) ? $field['required'] : ''}}"
                                :options="$productCategories" hint="{{ $field['hint'] }}" class="bg-[#FDFDFC]"
                                labelClass="" allowsNull="true" placeholder="{{ $field['placeholder'] }}" />
                        </div>

                        @elseif ($field['name'] === 'tax_id')
                        <!-- Product Tax -->
                        <div class="mb-5">
                            <x-select name="{{ $field['name'] }}" label="{{ $field['label'] }}"
                                id="{{ $field['name'] }}" valueField="id"
                                :selected="old($field['name'], $product->tax_id ?? '')"
                                required="{{ isset($field['required']) ? $field['required'] : ''}}" :options="$taxRates"
                                hint="{{ $field['hint'] }}" class="bg-[#FDFDFC]" labelClass="" allowsNull="true"
                                placeholder="{{ $field['placeholder'] }}" />
                        </div>

                        @elseif ($field['name'] === 'currency')
                        <!-- Product Currency -->
                        <div class="mb-5 md:col-span-2">
                            <x-select name="{{ $field['name'] }}" label="{{ $field['label'] }}"
                                id="{{ $field['name'] }}" valueField="id"
                                :selected="old($field['name'], $product->currency ?? '')"
                                required="{{ isset($field['required']) ? $field['required'] : ''}}"
                                :options="$field['options']" hint="{{ $field['hint'] }}" class="bg-[#FDFDFC]"
                                labelClass="" allowsNull="true" placeholder="{{ $field['placeholder'] }}" />
                        </div>

                        @elseif ($field['name'] === 'supplier_id')
                        <!-- Product Supplier -->
                        <div class="mb-5">
                            <x-select name="{{ $field['name'] }}" label="{{ $field['label'] }}"
                                id="{{ $field['name'] }}" valueField="id"
                                :selected="old($field['name'], $product->supplier_id ?? '')"
                                required="{{ isset($field['required']) ? $field['required'] : ''}}"
                                :options="$field['options']" hint="{{ $field['hint'] }}" class="bg-[#FDFDFC]"
                                labelClass="" allowsNull="true" placeholder="{{ $field['placeholder'] }}" />
                        </div>

                        @elseif (in_array($field['name'], $leftColumnFields))
                        <div
                            class="mb-5 @if($field['name'] === 'price')md:col-span-4 @elseif($field['name'] === 'currency')md:col-span-2 @else md:col-span-3 @endif ">
                            <label for="{{ $field['name'] }}"
                                class="block text-base font-medium text-gray-900 dark:text-white mb-2 h-6">
                                {{ $field['label'] }}
                                @if(isset($field['required']) && $field['required'])
                                <span class="text-red-500">*</span>
                                @endif
                            </label>
                            <input type="{{ $field['type'] }}" name="{{ $field['name'] }}" id="{{ $field['name'] }}"
                                class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500"
                                @if($field['name'] === 'price')
                                value="{{ old($field['name'], $product->{$field['name']}->toFloat() ?? '') }}"
                                @else
                                value="{{ old($field['name'], $product->{$field['name']} ?? '') }}"
                                @endif
                                @if(isset($field['required']) && $field['required']) required @endif>
                            @if(isset($field['hint']) && $field['hint'] !== '')
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $field['hint'] }}</p>
                            @endif
                            @error($field['name'])
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        @endif
                        @if ($field['name'] === 'slug' || $field['name'] === 'tax_id' || $field['name'] === 'currency')
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>

            <!-- Section 2: Product details -->
            <div
                class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-20 dark:shadow-gray-900/30 dark:bg-gray-800">
                <div class="p-6">
                    <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">{{
                        __('products.sections.detail_info') }}</h2>

                    <!-- Right column -->
                    @php
                    $leftColumnFields = ['name', 'slug', 'category_id', 'tax_id', 'price'];
                    @endphp
                    @foreach($fields as $field)
                    @if ($field['name'] === 'is_default')
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        @endif
                        @if (!in_array($field['name'], $leftColumnFields))
                        @if ($field['name'] === 'description')
                        <div class="mb-5">
                            <label for="{{ $field['name'] }}"
                                class="block text-base font-medium text-gray-900 dark:text-white mb-2 h-6">
                                {{ $field['label'] }}
                                @if(isset($field['required']) && $field['required'])
                                <span class="text-red-500">*</span>
                                @endif
                            </label>
                            <textarea name="{{ $field['name'] }}" id="{{ $field['name'] }}" rows="4"
                                class="form-textarea mt-1 block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500"
                                @if(isset($field['required']) && $field['required']) required
                                @endif>{{ old($field['name'], $product->{$field['name']} ?? '') }}</textarea>
                            @if(isset($field['hint']) && $field['hint'] !== '')
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $field['hint'] }}</p>
                            @endif
                            @error($field['name'])
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        @elseif ($field['name'] === 'image')
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="md:col-span-1 @if(!$errors->has('image'))mb-5 @endif">
                                <label for="image"
                                    class="flex flex-col items-center justify-center w-full h-37 border-2 border-gray-300 border-dashed rounded-sm cursor-pointer bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:border-gray-600 dark:hover:border-gray-500 dark:hover:bg-gray-600">
                                    <div class="flex flex-col items-center justify-center p-3">
                                        <p class="mb-2 text-lg font-semibold text-[#490BF4] dark:text-gray-200">
                                            {{ __('products.fields.upload_image') }}
                                        </p>
                                        <p class="mt-1 text-sm text-gray-500 wrap-break-word">
                                            {{ __('products.hints.image') }}
                                        </p>
                                    </div>
                                    <input id="image" name="image" type="file" class="hidden image-input" />
                                </label>
                            </div>
                            <div class="md:col-span-1 @if(!$errors->has('image'))mb-5 @endif">
                                <div id="image-preview-container">
                                    @if($product->image)
                                    <img id="current-image-preview"
                                        src="{{ Storage::disk('public')->url($product->image) }}"
                                        alt="{{ $product->name }}" class="max-h-40 object-cover rounded-sm">
                                    @else
                                    <p id="no-image-message" class="text-gray-500 dark:text-gray-400">{{
                                        __('products.messages.no_image_selected') }}</p>
                                    <img id="current-image-preview"
                                        src="{{ Storage::disk('public')->url('suppliers/logos/no_logo.png') }}"
                                        alt="{{ $product->name }}" class="max-h-40 object-cover rounded-sm hidden">
                                    @endif
                                </div>
                            </div>
                            @error('image')
                            <p class="text-sm text-red-600 mb-7">{{ $message }}</p>
                            @enderror
                        </div>

                        @elseif ($field['name'] === 'is_default')
                        <!-- Default product -->
                        <div class="mb-5">
                            <label for="is_default" class="flex items-center">
                                <input type="hidden" name="is_default" value="0">
                                <input type="checkbox" name="is_default" id="is_default" value="1" {{ old('is_default',
                                    $product->is_default) ? 'checked' : '' }}
                                class="border-2 border-blue-100 w-4 h-4 text-blue-600 bg-gray-100 rounded-sm
                                focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2
                                dark:bg-gray-700 dark:border-gray-600">
                                <span class="ml-2 text-base font-medium text-gray-900 dark:text-white">{{
                                    __('products.fields.is_default')
                                    }}</span>
                            </label>
                            @if(isset($field['hint']) && $field['hint'] !== '')
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $field['hint'] }}</p>
                            @endif
                        </div>

                        @elseif ($field['name'] === 'is_active')
                        <!-- Is active product -->
                        <div class="mb-5">
                            <label for="is_active" class="flex items-center">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active',
                                    $product->is_active) ? 'checked' : '' }}
                                class="border-2 border-blue-100 w-4 h-4 text-blue-600 bg-gray-100 rounded-sm
                                focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2
                                dark:bg-gray-700 dark:border-gray-600">
                                <span class="ml-2 text-base font-medium text-gray-900 dark:text-white">{{
                                    __('products.fields.is_active')
                                    }}</span>
                            </label>
                            @if(isset($field['hint']) && $field['hint'] !== '')
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $field['hint'] }}</p>
                            @endif
                        </div>
                        @endif
                        @endif
                        @if ($field['name'] === 'is_active')
                    </div>
                    @endif
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Form submit button -->
        <div class="flex justify-between mb-20 md:mb-10">
            <a href="{{ route('frontend.products', ['locale' => app()->getLocale()]) }}"
                class="inline-flex justify-center py-6 px-12 border border-white dark:border-gray-700 shadow-xl shadow-indigo-600/30 dark:shadow-gray-900/30 dark:shadow-md text-lg font-semibold rounded-sm text-gray-700 dark:text-white hover:text-gray-200 dark:hover:text-white bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                {{ __('products.actions.cancel') }}
            </a>
            <button type="submit"
                class="inline-flex items-center px-12 py-6 border border-transparent rounded-sm dark:shadow-md shadow-xl shadow-indigo-600/30 dark:shadow-gray-900/30 text-lg font-semibold text-white hover:text-white bg-[#490BF4] hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 cursor-pointer transition-colors duration-200">
                <i class="fas fa-save mr-2"></i>{{ __('products.actions.save') }}
            </button>
        </div>

        <!-- set Locale -->
        <input type="hidden" name="lang" value="{{ app()->getLocale() }}">
</form>

{{-- UELS Modal --}}
@auth
<x-uels-modal />
@endauth

@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Use globally registered SlugGenerator
        if (typeof window.SlugGenerator !== 'undefined') {
            new window.SlugGenerator('#name', '#slug', {
                overwriteExisting: false,
                enableEdit: true
            });
        } else {
            console.error('SlugGenerator not found. Make sure app.js is loaded properly.');
        }
    });
</script>
@vite('resources/js/image-preview.js')
@endpush

{{-- UELS JavaScript Integration --}}
@auth
<x-uels-scripts />
@endauth
