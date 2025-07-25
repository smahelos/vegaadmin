@extends('layouts.frontend')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl text-amber-600">{{ __('clients.titles.index') }}</h1>
    <a href="{{ route('frontend.client.create', ['locale' => app()->getLocale()]) }}" class="inline-flex items-center px-4 py-2 bg-blue-300 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-cyan-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
        <i class="fas fa-plus mr-2"></i> {{ __('clients.actions.new') }}
    </a>
</div>
<div class="grid grid-cols-1 gap-6">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <!-- Livewire component -->
                @livewire('ClientList')
            </div>
        </div>
</div>
@endsection

