@extends('layouts.frontend')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h1 class="main_title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4 leading-tight pt-3">{{ __('users.titles.edit_profile') }}</h1>
    <div class="flex-none space-x-2 space-y-2 grid grid-cols-3 md:flex md:grid-cols-none md:space-x-0">

        <x-back-button />

        <a href="{{ route('frontend.dashboard', ['locale' => app()->getLocale()]) }}"
            title="{{ __('users.actions.back_to_dashboard') }}"
            aria-label="{{ __('users.actions.back_to_dashboard') }}"
            class="px-4 py-2 col-span-1 md:col-span-1 md:ml-4 inline-block bg-gray-200 dark:bg-gray-800 hover:bg-white dark:hover:bg-gray-600 rounded-sm text-gray-700 dark:text-gray-200 text-sm font-medium transition-colors">
            <i class="fas fa-chart-bar"></i>
        </a>
        <span class="hidden"></span>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

<form method="POST" action="{{ route('frontend.profile.update', ['locale' => app()->getLocale()]) }}">
@csrf
    @method('PUT')
        <!-- Section 1: Basic informations -->
        <div class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-20 dark:shadow-gray-900/30 dark:bg-gray-800">
            <div class="p-6">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">{{ __('users.sections.basic_info') }}</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Name -->
                    <div class="mb-5">
                        <label for="name" class="block text-base font-medium text-gray-900 dark:text-white mb-2">
                            {{ __('users.fields.name') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                            name="name"
                            id="name"
                            value="{{ old('name', $user->name) }}"
                            required
                            class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500">
                        @error('name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-xs text-white">x</p>
                    </div>

                    <!-- Email -->
                    <div class="mb-5">
                        <label for="email" class="block text-base font-medium text-gray-900 dark:text-white mb-2">
                            {{ __('users.fields.email') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="email"
                            name="email"
                            id="email"
                            value="{{ old('email', $user->email) }}"
                            required
                            class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500">
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

    <!-- Submit -->
    <div class="flex justify-between">
        <a href="{{ route('frontend.dashboard', ['locale' => app()->getLocale()]) }}"
            class="inline-flex justify-center py-6 px-12 border border-white dark:border-gray-700 shadow-xl shadow-indigo-600/30 dark:shadow-gray-900/30 dark:shadow-md text-lg font-semibold rounded-sm text-gray-700 dark:text-white hover:text-gray-200 dark:hover:text-white bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
            {{ __('users.actions.cancel') }}
        </a>
        <button type="submit" class="inline-flex items-center px-12 py-6 border border-transparent rounded-sm dark:shadow-md shadow-xl shadow-indigo-600/30 dark:shadow-gray-900/30 text-lg font-semibold text-white hover:text-white bg-[#490BF4] hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 cursor-pointer transition-colors duration-200">
            <i class="fas fa-save mr-2"></i>
            {{ __('users.actions.save') }}
        </button>
    </div>

    <!-- set Locale -->
    <input type="hidden" name="lang" value="{{ app()->getLocale() }}">
</form>

<form method="POST" action="{{ route('frontend.profile.update.password', ['locale' => app()->getLocale()]) }}">
    @csrf
    @method('PUT')
    <!-- Section 2: Password change -->
    <div class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-20 dark:shadow-gray-900/30 dark:bg-gray-800">
        <div class="p-6">
            <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">{{ __('users.sections.change_password') }}</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Field for password change from trait -->
                    @foreach($passwordFields as $field)
                        <div class="mb-5">
                            <label for="{{ $field['name'] }}" class="block text-base font-medium text-gray-900 dark:text-white mb-2 h-6">
                                {{ $field['label'] }}
                                @if(isset($field['required']) && $field['required'])
                                    <span class="text-red-500">*</span>
                                @endif
                            </label>
                            <input type="{{ $field['type'] }}"
                                   name="{{ $field['name'] }}"
                                   id="{{ $field['name'] }}"
                                   class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500"
                                   @if(isset($field['required']) && $field['required']) required @endif>
                            @if(isset($field['hint']))
                                <p class="mt-2 text-xs text-gray-500 dark:text-gray-300">{{ $field['hint'] }}</p>
                            @endif
                            @error($field['name'])
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
            </div>
        </div>
    </div>
    <!-- Submit -->
    <div class="flex justify-end">
        <button type="submit" class="inline-flex items-center px-12 py-6 border border-transparent rounded-sm shadow-xl dark:shadow-md shadow-green-600/30 dark:shadow-gray-900/30 text-lg font-semibold text-white hover:text-white bg-green-600 hover:bg-green-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 cursor-pointer transition-colors duration-200">
            <i class="fas fa-key mr-2"></i>
            {{ __('users.actions.update_password') }}
        </button>
    </div>

    <!-- set Locale -->
    <input type="hidden" name="locale" value="{{ app()->getLocale() }}">
</form>
</div>
@endsection
