@extends('layouts.frontend')

@php
    // Helper functions to get fields by name
    function getField($fields, $name) {
        return collect($fields)->firstWhere('name', $name) ?? [];
    }
    
    function getRequired($field) {
        return isset($field['required']) && $field['required'] ? '<span class="text-red-500">*</span>' : '';
    }
@endphp

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h1 class="text-3xl text-amber-600">{{ __('users.titles.register') }}</h1>
    <div class="space-x-2">
        <a href="{{ route('frontend.login', ['locale' => app()->getLocale()]) }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-gray-700 text-sm font-medium transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>{{ __('users.actions.back_to_login') }}
        </a>
    </div>
</div>

<form method="POST" action="{{ route('frontend.register', ['locale' => app()->getLocale()]) }}">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Section 1: Basic Information -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg mb-8">
            <div class="p-6">
                <h2 class="text-2xl font-medium text-gray-900 mb-4">{{ __('users.sections.basic_info') }}</h2>
                
                <!-- Name -->
                @php $field = getField($userFields, 'name'); @endphp
                <div class="mb-5">
                    <label for="name" class="block text-base font-medium text-gray-500 mb-2">
                        {{ $field['label'] ?? __('users.fields.name') }} {!! getRequired($field) !!}
                    </label>
                    <input type="{{ $field['type'] ?? 'text' }}" 
                        name="name" 
                        id="name" 
                        value="{{ old('name') }}"
                        placeholder="{{ $field['placeholder'] ?? '' }}"
                        @if(isset($field['required']) && $field['required']) required @endif
                        class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-[#FDFDFC]">
                    @error('name')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Phone -->
                    @php $field = getField($userFields, 'phone'); @endphp
                    <div class="mb-5">
                        <label for="phone" class="block text-base font-medium text-gray-500 mb-2">
                            {{ $field['label'] ?? __('users.fields.phone') }} {!! getRequired($field) !!}
                        </label>
                        <input type="{{ $field['type'] ?? 'text' }}" 
                            name="phone" 
                            id="phone" 
                            value="{{ old('phone') }}"
                            placeholder="{{ $field['placeholder'] ?? '' }}"
                            @if(isset($field['required']) && $field['required']) required @endif
                            class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-[#FDFDFC]">
                        @error('phone')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    @php $field = getField($userFields, 'email'); @endphp
                    <div class="mb-5">
                        <label for="email" class="block text-base font-medium text-gray-500 mb-2">
                            {{ $field['label'] ?? __('users.fields.email') }} {!! getRequired($field) !!}
                        </label>
                        <input type="{{ $field['type'] ?? 'email' }}" 
                            name="email" 
                            id="email" 
                            value="{{ old('email') }}"
                            placeholder="{{ $field['placeholder'] ?? '' }}"
                            @if(isset($field['required']) && $field['required']) required @endif
                            class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-[#FDFDFC]">
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
                    <!-- Supplier description field -->
                    <div class="mb-4">
                        <label for="description" class="block text-sm font-medium text-gray-600 mb-1">
                            {{ __('suppliers.fields.description') }}
                        </label>
                        <textarea
                            id="description"
                            name="description"
                            rows="3"
                            class="form-textarea mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-[#FDFDFC]"
                        >{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Business ID -->
                    @php $field = getField($userFields, 'ico'); @endphp
                    <div class="mb-5">
                        <label for="ico" class="block text-base font-medium text-gray-500 mb-2">
                            {{ $field['label'] ?? __('suppliers.fields.ico') }} {!! getRequired($field) !!}
                        </label>
                        <input type="{{ $field['type'] ?? 'text' }}" 
                            name="ico" 
                            id="ico" 
                            value="{{ old('ico') }}"
                            placeholder="{{ $field['placeholder'] ?? '' }}"
                            @if(isset($field['required']) && $field['required']) required @endif
                            class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-[#FDFDFC]">
                        @error('ico')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                            
                    <!-- VAT Number -->
                    @php $field = getField($userFields, 'dic'); @endphp
                    <div class="mb-5">
                        <label for="dic" class="block text-base font-medium text-gray-500 mb-2">
                            {{ $field['label'] ?? __('suppliers.fields.dic') }} {!! getRequired($field) !!}
                        </label>
                        <input type="{{ $field['type'] ?? 'text' }}" 
                            name="dic" 
                            id="dic" 
                            value="{{ old('dic') }}"
                            placeholder="{{ $field['placeholder'] ?? '' }}"
                            @if(isset($field['required']) && $field['required']) required @endif
                            class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-[#FDFDFC]">
                        @error('dic')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    
        <!-- Section 2: Address -->
        <div class="bg-green-50 overflow-hidden shadow-sm rounded-lg border border-green-200 mb-8">
            <div class="p-6">
                <h2 class="text-2xl font-medium text-gray-900 mb-4">{{ __('users.sections.address') }}</h2>
                
                <!-- Street -->
                @php $field = getField($userFields, 'street'); @endphp
                <div class="mb-5">
                    <label for="street" class="block text-base font-medium text-gray-500 mb-2">
                        {{ $field['label'] ?? __('users.fields.street') }} {!! getRequired($field) !!}
                    </label>
                    <input type="{{ $field['type'] ?? 'text' }}" 
                        name="street" 
                        id="street" 
                        value="{{ old('street') }}"
                        placeholder="{{ $field['placeholder'] ?? '' }}"
                        @if(isset($field['required']) && $field['required']) required @endif
                        class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-[#FDFDFC]">
                    @error('street')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- City -->
                    @php $field = getField($userFields, 'city'); @endphp
                    <div class="mb-5">
                        <label for="city" class="block text-base font-medium text-gray-500 mb-2">
                            {{ $field['label'] ?? __('users.fields.city') }} {!! getRequired($field) !!}
                        </label>
                        <input type="{{ $field['type'] ?? 'text' }}" 
                            name="city" 
                            id="city" 
                            value="{{ old('city') }}"
                            placeholder="{{ $field['placeholder'] ?? '' }}"
                            @if(isset($field['required']) && $field['required']) required @endif
                            class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-[#FDFDFC]">
                        @error('city')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- ZIP Code -->
                    @php $field = getField($userFields, 'zip'); @endphp
                    <div class="mb-5">
                        <label for="zip" class="block text-base font-medium text-gray-500 mb-2">
                            {{ $field['label'] ?? __('users.fields.zip') }} {!! getRequired($field) !!}
                        </label>
                        <input type="{{ $field['type'] ?? 'text' }}" 
                            name="zip" 
                            id="zip" 
                            value="{{ old('zip') }}"
                            placeholder="{{ $field['placeholder'] ?? '' }}"
                            @if(isset($field['required']) && $field['required']) required @endif
                            class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-[#FDFDFC]">
                        @error('zip')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Country -->
                    @php $field = getField($userFields, 'country'); @endphp
                    <x-country-select 
                        name="country"
                        :selected="old('country', $client->country ?? 'CZ')" 
                        required="false"
                        label="{{ __('users.fields.country') }}"
                        />
                    
                </div>

                <div class="grid grid-cols-1 md:grid-cols-10 gap-6 mb-5">
                    <!-- Account Number -->
                    <div class="md:col-span-4">
                        <label for="account_number" class="block text-base font-medium text-gray-500 mb-2">
                            {{ __('suppliers.fields.account_number') }}
                        </label>
                        <input type="text" 
                            name="account_number" 
                            id="account_number" 
                            value="{{ old('account_number') }}" 
                            class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-[#FDFDFC]"
                            placeholder="123456789">
                        <p class="mt-2 text-xs text-gray-500">{{ __('suppliers.hints.account_number') }}</p>
                        @error('account_number')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Bank Code -->
                    <div class="md:col-span-3">
                            <x-select 
                                name="bank_code"
                                label="{{ __('suppliers.fields.bank_code') }}"
                                id="bank_code"
                                :selected="0" 
                                required="false"
                                :options="$banks" 
                                hint="{{ __('suppliers.hints.bank_code') }}"
                                class="bg-[#FDFDFC]" 
                                labelClass="" 
                                />
                        @error('bank_code')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- Bank Name -->
                    <div class="md:col-span-3">
                        <label for="bank_name" class="block text-base font-medium text-gray-500 mb-2">
                            {{ __('suppliers.fields.bank_name') }}
                        </label>
                        <input type="text" 
                            name="bank_name" 
                            id="bank_name" 
                            value="{{ old('bank_name') }}" 
                            class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-[#FDFDFC]"
                            ">
                        @error('bank_name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- IBAN -->
                    <div>
                        <label for="iban" class="block text-base font-medium text-gray-500 mb-2">
                            {{ __('suppliers.fields.iban') }}
                        </label>
                        <input type="text" 
                            name="iban" 
                            id="iban" 
                            value="{{ old('iban') }}" 
                            class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-[#FDFDFC] uppercase"
                            placeholder="CZ0000000000000000000000">
                        <p class="mt-2 text-xs text-gray-500">{{ __('suppliers.hints.iban') }}</p>
                        @error('iban')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    
                    <!-- SWIFT -->
                    <div>
                        <label for="swift" class="block text-base font-medium text-gray-500 mb-2">
                            {{ __('suppliers.fields.swift') }}
                        </label>
                        <input type="text" 
                            name="swift" 
                            id="swift" 
                            value="{{ old('swift') }}" 
                            class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-[#FDFDFC] uppercase"
                            placeholder="AAAACZPP">
                        <p class="mt-2 text-xs text-gray-500">{{ __('suppliers.hints.swift') }}</p>
                        @error('swift')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3: Account Security -->
    <div class="bg-blue-50 overflow-hidden shadow-sm rounded-lg mb-8 border border-blue-200">
        <div class="p-6">
            <h2 class="text-2xl font-medium text-gray-900 mb-4">{{ __('users.sections.security') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Password -->
                @php $field = collect($passwordFields)->firstWhere('name', 'password') ?? []; @endphp
                <div class="mb-5">
                    <label for="password" class="block text-base font-medium text-gray-500 mb-2">
                        {{ __('users.fields.password') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="password" 
                        name="password" 
                        id="password" 
                        required
                        placeholder="{{ $field['placeholder'] ?? '' }}"
                        class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-[#FDFDFC]">
                    @if(isset($field['hint']))
                        <p class="mt-1 text-xs text-gray-500">{{ $field['hint'] }}</p>
                    @endif
                    @error('password')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                
                <!-- Password Confirmation -->
                @php $field = collect($passwordFields)->firstWhere('name', 'password_confirmation') ?? []; @endphp
                <div class="mb-5">
                    <label for="password_confirmation" class="block text-base font-medium text-gray-500 mb-2">
                        {{ __('users.fields.password_confirmation') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="password" 
                        name="password_confirmation" 
                        id="password_confirmation" 
                        required
                        placeholder="{{ $field['placeholder'] ?? '' }}"
                        class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-[#FDFDFC]">
                    @if(isset($field['hint']))
                        <p class="mt-1 text-xs text-gray-500">{{ $field['hint'] }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <!-- Registration Buttons -->
    <div class="flex justify-between">
        <a href="{{ route('frontend.login', ['locale' => app()->getLocale()]) }}" class="inline-flex justify-center py-2 px-5 border border-gray-300 shadow-sm text-base font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            {{ __('users.messages.login_prompt') }}
        </a>
        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-base font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
            <i class="fas fa-user-plus mr-2"></i>
            {{ __('users.actions.register') }}
        </button>
    </div>

    <!-- set Locale -->
    <input type="hidden" name="lang" value="{{ app()->getLocale() }}">
</form>
@endsection

@push('scripts')
<script>
    // Make bank options available to the bank-fields.js script
    window.bankOptions = {{ Js::from($banksData) }};
</script>
@vite('resources/js/bank-fields.js')
@vite('resources/js/ares-lookup.js')
@endpush
