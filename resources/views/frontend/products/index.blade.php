@extends('layouts.frontend')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="main_title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4 leading-tight pt-3">{{
        __('products.titles.index') }} - {{ $limitsData['limit'] }}</h1>
    @if($limitsData['allowed'])
    <a href="{{ route('frontend.product.create', ['locale' => app()->getLocale()]) }}"
        class="inline-flex items-center px-4 py-2 bg-[#490BF4] border border-transparent rounded-sm font-bold text-sm text-white uppercase tracking-wide hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors duration-200">
        <i class="fas fa-plus mr-2"></i> {{ __('products.actions.create') }}
    </a>
    @endif
</div>

{{-- UELS Widget --}}
@auth
<div class="mb-6">
    <x-uels-limit-widget entity-type="product" :limit="$limitsData['limit']"
        entity-name="{{ __('uels.entities.product') }}" :show-modal="true" />
</div>
@endauth

<div class="grid grid-cols-1 gap-6">
    <div
        class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-20 dark:shadow-gray-900/30 dark:bg-gray-800">
        <div class="p-3">
            <!-- Livewire component -->
            @livewire('product.product-list')
        </div>
    </div>
</div>

{{-- UELS Modal --}}
@auth
<x-uels-modal />
@endauth

@endsection

{{-- UELS JavaScript Integration --}}
@auth
<x-uels-scripts />
@endauth
