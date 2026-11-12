@use('App\Application\Party\Commands\CreateContact')
@extends('layouts.frontend')

@use('App\Domain\User\Enums\LimitedEntity')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="main_title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4 leading-tight pt-3">{{ __('contacts.titles.index') }}</h1>
    @allowed(CreateContact::class)
        <a href="{{ route('frontend.contacts.create', ['locale' => App::getLocale()]) }}"
            class="inline-flex items-center px-4 py-2 bg-[#490BF4] rounded-sm font-bold text-sm text-white uppercase tracking-wide hover:bg-indigo-500 transition-colors duration-200">
            <i class="fas fa-plus mr-2"></i> {{ __('contacts.actions.create') }}
        </a>
    @endallowed
</div>

<div class="mb-6">
    <x-entity-usage :entity="LimitedEntity::CONTACT" />
</div>

<x-form.card class="mb-20">
    <livewire:party.contact-list />
</x-form.card>
@endsection
