@extends('layouts.frontend')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="main_title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4 leading-tight pt-3">{{ __('invoices.titles.index') }}</h1>
    <a href="{{ route('frontend.invoice.create', ['locale' => app()->getLocale()]) }}" class="inline-flex items-center px-4 py-2 bg-[#490BF4] border border-transparent rounded-sm font-bold text-sm text-white uppercase tracking-wide hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors duration-200">
        <i class="fas fa-plus mr-2"></i> {{ __('invoices.actions.create') }}
    </a>
</div>

{{-- UELS Widget --}}
@auth
<div class="mb-6">
    <x-uels-limit-widget entity-type="invoice" limit="{{ $limitsData['limit'] }}" entity-name="{{ __('uels.entities.invoice') }}" :show-modal="true" />
</div>
@endauth

<div class="grid grid-cols-1 gap-6">
    <div class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-20 dark:shadow-gray-900/30 dark:bg-gray-800">
        <div class="p-3">
            <!-- Livewire component -->
            @livewire('invoice.invoice-list')
        </div>
    </div>
</div>

{{-- UELS Modal --}}
@auth
<x-uels-modal />
@endauth
@endsection

@push('after_scripts')
{{-- UELS JavaScript Integration --}}
@auth
<x-uels-scripts />
@endauth
@endpush
