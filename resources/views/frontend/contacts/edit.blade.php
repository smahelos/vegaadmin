@extends('layouts.frontend')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h1 class="main_title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4 leading-tight pt-3">{{ __('contacts.titles.edit') }}</h1>
    <div class="flex flex-wrap items-center gap-2 md:gap-4">
        <x-back-button />
        <x-icon-button icon="fa-list" :label="__('contacts.actions.back_to_list')"
            :href="route('frontend.contacts.index', ['locale' => app()->getLocale()])" />
    </div>
</div>

<livewire:party.contact-form :contact-id="$contactId" />
@endsection
