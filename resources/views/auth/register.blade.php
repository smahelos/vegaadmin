@extends('layouts.frontend')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h1 class="main_title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4 leading-tight pt-3">{{
        __('users.titles.register') }}</h1>
    <x-back-button />
</div>

<livewire:user.registration-form />
@endsection
