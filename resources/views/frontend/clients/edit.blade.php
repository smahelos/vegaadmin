@extends('layouts.frontend')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h1 class="main_title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4 leading-tight pt-3">{{
        __('clients.titles.edit') }}</h1>
    <div class="flex-none space-x-2 space-y-2 grid grid-cols-2 md:flex md:grid-cols-none md:space-x-0">

        <x-back-button />

        <a href="{{ route('frontend.clients', ['locale' => app()->getLocale()]) }}"
            title="{{ __('clients.actions.back_to_list') }}" aria-label="{{ __('clients.actions.back_to_list') }}"
            class="px-4 py-2 col-span-1 md:col-span-1 md:ml-4 inline-block bg-gray-200 dark:bg-gray-800 hover:bg-white dark:hover:bg-gray-600 rounded-sm text-gray-700 dark:text-gray-200 text-sm font-medium transition-colors">
            <i class="fas fa-list"></i>
        </a>
        <span class="hidden"></span>
    </div>
</div>

{{-- UELS Widget --}}
@auth
<div class="mb-6">
    <x-uels-limit-widget entity-type="client" limit="{{ $limitsData['limit'] }}" entity-name="{{ __('uels.entities.client') }}" :show-modal="true" />
</div>
@endauth

<form action="{{ route('frontend.client.update', ['locale' => app()->getLocale(), $client->id]) }}" method="POST">
    @csrf
    @method('PUT')

    <!-- Section 1: Client Basic Information -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div
            class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-20 dark:shadow-gray-900/30 dark:bg-gray-800">
            <div class="p-6">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">{{
                    __('clients.sections.basic_info') }}</h2>

                <!-- Left column -->
                @php
                $leftColumnFields = ['name', 'shortcut', 'email', 'phone'];
                @endphp
                @foreach($fields as $field)
                @if ($field['name'] === 'name' || $field['name'] === 'email')
                <div class="grid grid-cols-1 md:grid-cols-6 gap-6">
                    @endif
                    @if (in_array($field['name'], $leftColumnFields))
                    <div
                        class="mb-5 @if($field['name'] === 'name')md:col-span-4 @elseif($field['name'] === 'shortcut')md:col-span-2 @else md:col-span-3 @endif ">
                        <label for="{{ $field['name'] }}"
                            class="block text-base font-medium text-gray-900 dark:text-white mb-2 h-6">
                            {{ $field['label'] }}
                            @if(isset($field['required']) && $field['required'])
                            <span class="text-red-500">*</span>
                            @endif
                        </label>
                        <input type="{{ $field['type'] }}" name="{{ $field['name'] }}" id="{{ $field['name'] }}"
                            value="{{ old($field['name'], $client->{$field['name']}) }}"
                            class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500"
                            @if(isset($field['required']) && $field['required']) required @endif>
                        @if(isset($field['hint']) && $field['hint'] !== '')
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $field['hint'] }}</p>
                        @endif
                        @error($field['name'])
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    @endif
                    @if ($field['name'] === 'shortcut' || $field['name'] === 'phone')
                </div>
                @endif

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
                        @endif>{{ old($field['name'], $client->{$field['name']}) }}</textarea>
                    @if(isset($field['hint']) && $field['hint'] !== '')
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $field['hint'] }}</p>
                    @endif
                    @error($field['name'])
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                @endif

                @if ($field['name'] === 'is_default')
                <!-- Default client -->
                <div class="mb-5 is-default-{{ $client->is_default }}">
                    <label for="is_default" class="flex items-center">
                        <input type="hidden" name="is_default" value="0">
                        <input type="checkbox" name="is_default" id="is_default" value="1"
                            class="border-2 border-blue-100 w-4 h-4 text-blue-600 bg-gray-100 rounded-sm focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                            {{ old('is_default', $client->is_default) ? 'checked' : '' }}>
                        <span class="ml-2 text-base font-medium text-gray-900 dark:text-white">{{
                            __('clients.fields.is_default')
                            }}</span>
                    </label>
                    @if(isset($field['hint']) && $field['hint'] !== '')
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $field['hint'] }}</p>
                    @endif
                </div>
                @endif
                @endforeach
            </div>
        </div>

        <!-- Section 2: Client Billing Information -->
        <div
            class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-20 dark:shadow-gray-900/30 dark:bg-gray-800">
            <div class="p-6">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">{{
                    __('clients.sections.billing_info') }}</h2>

                <!-- Right column -->
                @php
                $leftColumnFields = ['name', 'shortcut', 'email', 'phone', 'description', 'is_default'];
                @endphp
                @foreach($fields as $field)
                @if ($field['name'] === 'city' || $field['name'] === 'ico')
                <div class="grid grid-cols-1 md:grid-cols-10 gap-6">
                    @endif
                    <div
                        class="@if($field['name'] === 'city')md:col-span-4 @elseif($field['name'] === 'zip')md:col-span-3 @elseif($field['name'] === 'country')md:col-span-3 @else md:col-span-5 @endif ">
                        @if ($field['name'] === 'country')
                        <!-- Country -->
                        <x-country-select name="country" :selected="old('country', $client->country ?? 'CZ')"
                            required="true" label="{{ __('clients.fields.country') }}" />
                        @elseif (!in_array($field['name'], $leftColumnFields))
                        <div class="mb-5">
                            <label for="{{ $field['name'] }}"
                                class="block text-base font-medium text-gray-900 dark:text-white mb-2 h-6">
                                {{ $field['label'] }}
                                @if(isset($field['required']) && $field['required'])
                                <span class="text-red-500">*</span>
                                @endif
                            </label>
                            <input type="{{ $field['type'] }}" name="{{ $field['name'] }}" id="{{ $field['name'] }}"
                                value="{{ old($field['name'], $client->{$field['name']}) }}"
                                class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500"
                                @if(isset($field['required']) && $field['required']) required @endif>
                            @if(isset($field['hint']) && $field['hint'] !== '')
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ $field['hint'] }}</p>
                            @endif
                            @error($field['name'])
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        @endif
                    </div>
                    @if ($field['name'] === 'country' || $field['name'] === 'dic')
                </div>
                @endif
                @endforeach
            </div>
        </div>
    </div>

    <!-- Form submit button -->
    <div class="flex justify-between mb-20 md:mb-10">
        <a href="{{ route('frontend.clients', ['locale' => app()->getLocale()]) }}"
            class="inline-flex justify-center py-6 px-12 border border-white dark:border-gray-700 shadow-xl shadow-indigo-600/30 dark:shadow-gray-900/30 dark:shadow-md text-lg font-semibold rounded-sm text-gray-700 dark:text-white hover:text-gray-200 dark:hover:text-white bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
            {{ __('clients.actions.cancel') }}
        </a>
        <button type="submit"
            class="inline-flex items-center px-12 py-6 border border-transparent rounded-sm dark:shadow-md shadow-xl shadow-indigo-600/30 dark:shadow-gray-900/30 text-lg font-semibold text-white hover:text-white bg-[#490BF4] hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 cursor-pointer transition-colors duration-200">
            <i class="fas fa-save mr-2"></i>{{ __('clients.actions.save_changes') }}
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
@vite('resources/js/ares-lookup.js')
@endpush

{{-- UELS JavaScript Integration --}}
@auth
<x-uels-scripts />
@endauth
