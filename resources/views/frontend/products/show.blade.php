@extends('layouts.frontend')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl text-amber-600"><span class="text-base text-gray-900 font-medium">{{ __('products.titles.show') }}</span> {{ $product->name }}</h1>
    <div class="flex space-x-4">

        <x-back-button />
        
        <a href="@localizedRoute('frontend.products')" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-gray-700 text-sm font-medium transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> {{ __('products.actions.back_to_list') }}
        </a>
        <a href="@localizedRoute('frontend.product.edit', $product->id)" class="px-4 py-2 bg-green-200 hover:bg-emerald-500 rounded-md text-sm text-gray-700 hover:text-white font-medium transition-colors">
            <i class="fas fa-pencil-alt pr-2"></i> {{ __('products.actions.edit') }}
        </a>
    </div>
</div>
<!-- Main product information -->
<div class="mb-6">
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Left column: Basic information -->
                <div class="p-6 overflow-hidden shadow-md bg-blue-50 rounded-lg border border-blue-200">
                    <div class="border-b border-blue-200 mb-4 pb-2 flex justify-between items-center">
                        <h2 class="text-xl text-gray-800">{{ __('products.sections.basic_info') }}</h2>
                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">{{ __('products.tags.product') }}</span>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex flex-wrap">
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-green-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('products.fields.name') }}</div>
                                    <div class="font-medium">{{ $product->name }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-green-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('products.fields.slug') }}</div>
                                    <div class="font-medium">{{ $product->slug }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-orange-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('products.fields.price') }}</div>
                                    <div class="font-medium">{{ number_format($product->price, 2, ',', ' ') }} {{ $product->currency }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-orange-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('products.fields.category_id') }}</div>
                                    <div class="font-medium">{{ $product->category->slug ?? __('general.empty.not_specified') }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-yellow-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('products.fields.created_at') }}</div>
                                    <div class="font-medium">{{ \Carbon\Carbon::parse($product->created_at)->format(App\Helpers\DateHelper::format()) }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-yellow-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('products.fields.tax_id') }}</div>
                                    <div class="font-medium">
                                        @if($product->tax)
                                            {{ $product->tax->rate }}%
                                        @else
                                            {{ __('general.empty.not_specified') }}
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        @if(!empty($product->description))
                        <div class="bg-white p-3 rounded-md shadow-sm">
                            <div class="text-xs text-gray-500 mb-1">{{ __('products.fields.description') }}</div>
                            <div class="text-sm whitespace-pre-line">{{ $product->description }}</div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Right column: Additional information -->
                <div class="p-6 overflow-hidden shadow-md bg-green-50 rounded-lg border border-green-200">
                    <div class="border-b border-green-200 mb-4 pb-2 flex justify-between items-center">
                        <h2 class="text-xl text-gray-800">{{ __('products.sections.detail_info') }}</h2>
                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">{{ __('products.tags.details') }}</span>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex flex-wrap">
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-orange-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('products.fields.is_default') }}</div>
                                    <div class="font-medium">
                                        <span class="font-semibold @if($product->is_default > 0)text-green-600 @else text-red-600 @endif">
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
                                    <div class="text-xs text-gray-500">{{ __('products.fields.is_active') }}</div>
                                    <div class="font-medium">
                                        <span class="font-semibold @if($product->is_active > 0)text-green-600 @else text-red-600 @endif">
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
                        <div class="bg-white p-3 rounded-md shadow-sm">
                            <div class="text-sm font-medium mb-2">{{ __('products.fields.image') }}</div>
                            <div class="flex justify-center">
                                <img src="{{ Storage::disk('public')->url($product->image) }}" alt="{{ $product->name }}" class="max-h-60 object-contain rounded-md">
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
</div>

<!-- We could add related invoices/orders here if needed -->
@endsection
