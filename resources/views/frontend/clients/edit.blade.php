@extends('layouts.frontend')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h1 class="text-3xl text-amber-600">{{ __('clients.titles.edit') }}</h1>
    <div class="space-x-2">

        <x-back-button />
        
        <a href="@localizedRoute('frontend.clients')"
            class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-gray-700 text-sm font-medium transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>{{ __('clients.actions.back_to_list') }}
        </a>
    </div>
</div>

<form action="{{ route('frontend.client.update', $client->id) }}" method="POST">
    @csrf
    @method('PUT')

    <!-- Section 1: Client Basic Information -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <h2 class="text-2xl font-medium text-gray-900 mb-4">{{ __('clients.sections.basic_info') }}</h2>

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
                        <label for="{{ $field['name'] }}" class="block text-base font-medium text-gray-500 mb-2 h-6">
                            {{ $field['label'] }}
                            @if(isset($field['required']) && $field['required'])
                            <span class="text-red-500">*</span>
                            @endif
                        </label>
                        <input type="{{ $field['type'] }}" name="{{ $field['name'] }}" id="{{ $field['name'] }}"
                            value="{{ old($field['name'], $client->{$field['name']}) }}"
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
                    @if ($field['name'] === 'shortcut' || $field['name'] === 'phone')
                </div>
                @endif

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
                        @if(isset($field['required']) && $field['required']) required
                        @endif>{{ old($field['name'], $client->{$field['name']}) }}</textarea>
                    @if(isset($field['hint']) && $field['hint'] !== '')
                    <p class="mt-2 text-xs text-gray-500">{{ $field['hint'] }}</p>
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
                            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50"
                            {{ old('is_default', $client->is_default) ? 'checked' : '' }}>
                        <span class="ml-2 text-base font-medium text-gray-700">{{ __('clients.fields.is_default')
                            }}</span>
                    </label>
                    @if(isset($field['hint']) && $field['hint'] !== '')
                    <p class="mt-2 text-xs text-gray-500">{{ $field['hint'] }}</p>
                    @endif
                </div>
                @endif
                @endforeach
            </div>
        </div>

        <!-- Section 2: Client Billing Information -->
        <div class="bg-green-50 overflow-hidden shadow-sm rounded-lg border border-green-200">
            <div class="p-6">
                <h2 class="text-2xl font-medium text-gray-900 mb-4">{{ __('clients.sections.billing_info') }}</h2>

                <!-- Right column -->
                @php
                $leftColumnFields = ['name', 'shortcut', 'email', 'phone', 'description', 'is_default'];
                @endphp
                @foreach($fields as $field)
                @if ($field['name'] === 'city' || $field['name'] === 'ico')
                <div class="grid grid-cols-1 md:grid-cols-10 gap-6">
                    @endif
                    <div
                        class="@if($field['name'] === 'city')md:col-span-5 @elseif($field['name'] === 'zip')md:col-span-3 @elseif($field['name'] === 'country')md:col-span-2 @else md:col-span-5 @endif ">
                        @if ($field['name'] === 'country')
                        <!-- Country -->
                        <x-country-select name="country" :selected="old('country', $client->country ?? 'CZ')"
                            required="true" label="{{ __('clients.fields.country') }}" />
                        @elseif (!in_array($field['name'], $leftColumnFields))
                        <div class="mb-5">
                            <label for="{{ $field['name'] }}"
                                class="block text-base font-medium text-gray-500 mb-2 h-6">
                                {{ $field['label'] }}
                                @if(isset($field['required']) && $field['required'])
                                <span class="text-red-500">*</span>
                                @endif
                            </label>
                            <input type="{{ $field['type'] }}" name="{{ $field['name'] }}" id="{{ $field['name'] }}"
                                value="{{ old($field['name'], $client->{$field['name']}) }}"
                                class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-[#FDFDFC]"
                                @if(isset($field['required']) && $field['required']) required @endif>
                            @if(isset($field['hint']) && $field['hint'] !== '')
                            <p class="mt-2 text-xs text-gray-500">{{ $field['hint'] }}</p>
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
    <div class="mt-6 flex justify-between">
        <a href="@localizedRoute('frontend.clients')"
            class="inline-flex justify-center py-2 px-5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            {{ __('clients.actions.cancel') }}
        </a>
        <button type="submit"
            class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:text-white bg-emerald-500 hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 cursor-pointer">
            <i class="fas fa-save mr-2"></i>{{ __('clients.actions.save_changes') }}
        </button>
    </div>

    <!-- set Locale -->
    <input type="hidden" name="lang" value="{{ app()->getLocale() }}">
</form>
@endsection

@push('scripts')
@vite('resources/js/ares-lookup.js')
@endpush
