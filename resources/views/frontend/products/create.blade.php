@extends('layouts.frontend')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h1 class="text-3xl text-amber-600">{{ __('products.actions.create') }}</h1>
    <div class="space-x-2">
        <a href="@localizedRoute('frontend.products')"
            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-gray-700 text-sm font-medium transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>{{ __('products.actions.back_to_list') }}
        </a>
    </div>
</div>

<form action="{{ route('frontend.product.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <h2 class="text-2xl font-medium text-gray-900 mb-4">{{ __('products.sections.basic_info') }}</h2>

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
                                required="{{ isset($field['required']) ? $field['required'] : ''}}"
                                :options="$taxRates" hint="{{ $field['hint'] }}" class="bg-[#FDFDFC]"
                                labelClass="" allowsNull="true" placeholder="{{ $field['placeholder'] }}" />
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
                            class="mb-5 @if($field['name'] === 'name' || $field['name'] === 'price')md:col-span-4 @elseif($field['name'] === 'slug' || $field['name'] === 'currency')md:col-span-2 @else md:col-span-3 @endif ">
                            <label for="{{ $field['name'] }}" class="block text-base font-medium text-gray-500 mb-2 h-6">
                                {{ $field['label'] }}
                                @if(isset($field['required']) && $field['required'])
                                <span class="text-red-500">*</span>
                                @endif
                            </label>
                            <input type="{{ $field['type'] }}" name="{{ $field['name'] }}" id="{{ $field['name'] }}"
                                class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-blue-50"
                                @if(isset($field['required']) && $field['required']) required @endif>
                            @if(isset($field['hint']) && $field['hint'] !== '')
                            <p class="mt-2 text-xs text-gray-500">{{ $field['hint'] }}</p>
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
        <div class="bg-green-50 overflow-hidden shadow-sm rounded-lg border border-green-200">
            <div class="p-6">
                <h2 class="text-2xl font-medium text-gray-900 mb-4">{{ __('products.sections.detail_info') }}</h2>

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
                                    <label for="{{ $field['name'] }}" class="block text-base font-medium text-gray-500 mb-2 h-6">
                                        {{ $field['label'] }}
                                        @if(isset($field['required']) && $field['required'])
                                        <span class="text-red-500">*</span>
                                        @endif
                                    </label>
                                    <textarea name="{{ $field['name'] }}" id="{{ $field['name'] }}" rows="4"
                                        class="form-textarea mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-blue-50"
                                        @if(isset($field['required']) && $field['required']) required @endif></textarea>
                                    @if(isset($field['hint']) && $field['hint'] !== '')
                                    <p class="mt-2 text-xs text-gray-500">{{ $field['hint'] }}</p>
                                    @endif
                                    @error($field['name'])
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                            @elseif ($field['name'] === 'image')
                                <!-- Product Image -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="mb-5">
                                        <label for="{{ $field['name'] }}" class="block text-base font-medium text-gray-500 mb-2 h-6">
                                            {{ $field['label'] }}
                                            @if(isset($field['required']) && $field['required'])
                                            <span class="text-red-500">*</span>
                                            @endif
                                        </label>
                                        <input type="file" name="{{ $field['name'] }}" id="{{ $field['name'] }}"
                                            class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-blue-50 product-image-input"
                                            accept="image/*" @if(isset($field['required']) && $field['required']) required @endif>
                                        @if(isset($field['hint']) && $field['hint'] !== '')
                                        <p class="mt-2 text-xs text-gray-500">{{ $field['hint'] }}</p>
                                        @endif
                                        @error($field['name'])
                                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="mb-5">
                                        <label for="current_image" class="block text-base font-medium text-gray-500 mb-2 h-6">
                                            {{ __('products.no_input_labels.current_image') }}
                                        </label>
                                        <div id="image-preview-container">
                                            <p id="no-image-message" class="text-gray-500">{{ __('products.messages.no_image_selected') }}</p>
                                            <img id="current-image-preview" src="" alt="" class="max-h-40 object-cover rounded-md hidden">
                                        </div>
                                    </div>
                                </div>

                            @elseif ($field['name'] === 'is_default')
                                <!-- Default product -->
                                <div class="mb-5">
                                    <label for="is_default" class="flex items-center">
                                        <input type="hidden" name="is_default" value="0">
                                        <input type="checkbox" name="is_default" id="is_default" value="1"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <span class="ml-2 text-base font-medium text-gray-700">{{ __('products.fields.is_default')
                                            }}</span>
                                    </label>
                                    @if(isset($field['hint']) && $field['hint'] !== '')
                                    <p class="mt-2 text-xs text-gray-500">{{ $field['hint'] }}</p>
                                    @endif
                                </div>

                            @elseif ($field['name'] === 'is_active')
                                <!-- Is active product -->
                                <div class="mb-5">
                                    <label for="is_active" class="flex items-center">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" id="is_active" value="1"
                                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                        <span class="ml-2 text-base font-medium text-gray-700">{{ __('products.fields.is_active')
                                            }}</span>
                                    </label>
                                    @if(isset($field['hint']) && $field['hint'] !== '')
                                    <p class="mt-2 text-xs text-gray-500">{{ $field['hint'] }}</p>
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
    <div class="mt-6 flex justify-between">
        <a href="@localizedRoute('frontend.products')"
            class="inline-flex justify-center py-2 px-5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            {{ __('products.actions.cancel') }}
        </a>
        <button type="submit"
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:text-white bg-emerald-500 hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 cursor-pointer">
            <i class="fas fa-save mr-2"></i>{{ __('products.actions.create') }}
        </button>
    </div>

    <!-- set Locale -->
    <input type="hidden" name="lang" value="{{ app()->getLocale() }}">
</form>

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
@vite('resources/js/product-image-preview.js')
@endpush
