@extends('layouts.frontend')

@php /* Removed global helper functions to avoid redeclare errors in compiled views */ @endphp

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h1 class="main_title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4 leading-tight pt-3">{{
        __('users.titles.register') }}</h1>
    <div class="flex-none space-x-2 space-y-2 grid grid-cols-1 md:flex md:grid-cols-none md:space-x-0">

        <x-back-button />
        <span class="hidden"></span>
    </div>
</div>

<form method="POST" action="{{ route('frontend.register', ['locale' => app()->getLocale()]) }}">
    @csrf
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Section 1: Basic Information -->
        <div
            class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-10 dark:shadow-gray-900/30 dark:bg-gray-800">
            <div class="p-6">
                <h2 class="text-2xl font-semibold text-gray-900 dark:text-white mb-4">{{ __('users.sections.basic_info')
                    }}</h2>

                <!-- Name -->
                @php $field = collect($userFields)->firstWhere('name', 'name') ?? []; @endphp
                <div class="mb-5">
                    <label for="name" class="block text-base font-medium text-gray-900 dark:text-white mb-2">
                        {{ $field['label'] ?? __('users.fields.name') }} @if(isset($field['required']) &&
                        $field['required'])<span class="text-red-500">*</span>@endif
                    </label>
                    <input type="{{ $field['type'] ?? 'text' }}" name="name" id="name" value="{{ old('name') }}"
                        placeholder="{{ $field['placeholder'] ?? '' }}" @if(isset($field['required']) &&
                        $field['required']) required @endif
                        class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500">
                    @error('name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Phone -->
                    @php $field = collect($userFields)->firstWhere('name', 'phone') ?? []; @endphp
                    <div class="mb-5">
                        <label for="phone" class="block text-base font-medium text-gray-900 dark:text-white mb-2">
                            {{ $field['label'] ?? __('users.fields.phone') }} @if(isset($field['required']) &&
                            $field['required'])<span class="text-red-500">*</span>@endif
                        </label>
                        <input type="{{ $field['type'] ?? 'text' }}" name="phone" id="phone" value="{{ old('phone') }}"
                            placeholder="{{ $field['placeholder'] ?? '' }}" @if(isset($field['required']) &&
                            $field['required']) required @endif
                            class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500">
                        @error('phone')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    @php $field = collect($userFields)->firstWhere('name', 'email') ?? []; @endphp
                    <div class="mb-5">
                        <label for="email" class="block text-base font-medium text-gray-900 dark:text-white mb-2">
                            {{ $field['label'] ?? __('users.fields.email') }} @if(isset($field['required']) &&
                            $field['required'])<span class="text-red-500">*</span>@endif
                        </label>
                        <input type="{{ $field['type'] ?? 'email' }}" name="email" id="email" value="{{ old('email') }}"
                            placeholder="{{ $field['placeholder'] ?? '' }}" @if(isset($field['required']) &&
                            $field['required']) required @endif
                            class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500">
                        @error('email')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-1 gap-6">
                    <!-- Supplier description field -->
                    <div class="mb-4">
                        <label for="description" class="block text-base font-medium text-gray-900 dark:text-white mb-1">
                            {{ __('suppliers.fields.description') }}
                        </label>
                        <textarea id="description" name="description" rows="3"
                            class="form-textarea mt-1 block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500">{{ old('description') }}</textarea>
                        @error('description')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <!-- Business ID -->
                    @php $field = collect($userFields)->firstWhere('name', 'ico') ?? []; @endphp
                    <div class="mb-5 md:grid-cols-2">
                        <label for="ico" class="block text-base font-medium text-gray-900 dark:text-white mb-2">
                            {{ $field['label'] ?? __('suppliers.fields.ico') }} @if(isset($field['required']) &&
                            $field['required'])<span class="text-red-500">*</span>@endif
                        </label>
                        <input type="{{ $field['type'] ?? 'text' }}" name="ico" id="ico" value="{{ old('ico') }}"
                            placeholder="{{ $field['placeholder'] ?? '' }}" @if(isset($field['required']) &&
                            $field['required']) required @endif
                            class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500">
                        @error('ico')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- VAT Number -->
                    @php $field = collect($userFields)->firstWhere('name', 'dic') ?? []; @endphp
                    <div class="mb-5 md:col-span-2">
                        <label for="dic" class="w-full block text-base font-medium text-gray-900 dark:text-white mb-2">
                            {{ $field['label'] ?? __('suppliers.fields.dic') }} @if(isset($field['required']) &&
                            $field['required'])<span class="text-red-500">*</span>@endif
                        </label>
                        <input type="{{ $field['type'] ?? 'text' }}" name="dic" id="dic" value="{{ old('dic') }}"
                            placeholder="{{ $field['placeholder'] ?? '' }}" @if(isset($field['required']) &&
                            $field['required']) required @endif
                            class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500">
                        @error('dic')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Section 2: Address -->
        <div
            class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-10 dark:shadow-gray-900/30 dark:bg-gray-800">
            <div class="p-6">
                <h2 class="text-2xl font-medium text-gray-900 dark:text-white mb-4">{{ __('users.sections.address') }}
                </h2>

                <!-- Street -->
                @php $field = collect($userFields)->firstWhere('name', 'street') ?? []; @endphp
                <div class="mb-5">
                    <label for="street" class="block text-base font-medium text-gray-900 dark:text-white mb-2">
                        {{ $field['label'] ?? __('users.fields.street') }} @if(isset($field['required']) &&
                        $field['required'])<span class="text-red-500">*</span>@endif
                    </label>
                    <input type="{{ $field['type'] ?? 'text' }}" name="street" id="street" value="{{ old('street') }}"
                        placeholder="{{ $field['placeholder'] ?? '' }}" @if(isset($field['required']) &&
                        $field['required']) required @endif
                        class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500">
                    @error('street')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- City -->
                    @php $field = collect($userFields)->firstWhere('name', 'city') ?? []; @endphp
                    <div class="mb-5">
                        <label for="city" class="block text-base font-medium text-gray-900 dark:text-white mb-2">
                            {{ $field['label'] ?? __('users.fields.city') }} @if(isset($field['required']) &&
                            $field['required'])<span class="text-red-500">*</span>@endif
                        </label>
                        <input type="{{ $field['type'] ?? 'text' }}" name="city" id="city" value="{{ old('city') }}"
                            placeholder="{{ $field['placeholder'] ?? '' }}" @if(isset($field['required']) &&
                            $field['required']) required @endif
                            class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500">
                        @error('city')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- ZIP Code -->
                    @php $field = collect($userFields)->firstWhere('name', 'zip') ?? []; @endphp
                    <div class="mb-5">
                        <label for="zip" class="block text-base font-medium text-gray-900 dark:text-white mb-2">
                            {{ $field['label'] ?? __('users.fields.zip') }} @if(isset($field['required']) &&
                            $field['required'])<span class="text-red-500">*</span>@endif
                        </label>
                        <input type="{{ $field['type'] ?? 'text' }}" name="zip" id="zip" value="{{ old('zip') }}"
                            placeholder="{{ $field['placeholder'] ?? '' }}" @if(isset($field['required']) &&
                            $field['required']) required @endif
                            class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500">
                        @error('zip')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Country -->
                    @php /* No need to assign $field for country here */ @endphp
                    <x-country-select name="country" :selected="old('country', $client->country ?? 'CZ')"
                        required="false" label="{{ __('users.fields.country') }}" />

                </div>

                <div class="grid grid-cols-1 md:grid-cols-10 gap-6 mb-5">
                    <!-- Account Number -->
                    <div class="md:col-span-4">
                        <label for="account_number"
                            class="block text-base font-medium text-gray-900 dark:text-white mb-2">
                            {{ __('suppliers.fields.account_number') }}
                        </label>
                        <input type="text" name="account_number" id="account_number" value="{{ old('account_number') }}"
                            class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500"
                            placeholder="123456789">
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('suppliers.hints.account_number')
                            }}</p>
                        @error('account_number')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Bank Code -->
                    <div class="md:col-span-3">
                        <x-select name="bank_code" label="{{ __('suppliers.fields.bank_code') }}" id="bank_code"
                            :selected="0" required="false" :options="$banks"
                            hint="{{ __('suppliers.hints.bank_code') }}" class="bg-[#FDFDFC]" labelClass="" />
                        @error('bank_code')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Bank Name -->
                    <div class="md:col-span-3">
                        <label for="bank_name" class="block text-base font-medium text-gray-900 dark:text-white mb-2">
                            {{ __('suppliers.fields.bank_name') }}
                        </label>
                        <input type="text" name="bank_name" id="bank_name" value="{{ old('bank_name') }}"
                            class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500" ">
                        @error('bank_name')
                            <p class=" mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- IBAN -->
                    <div>
                        <label for="iban" class="block text-base font-medium text-gray-900 dark:text-white mb-2">
                            {{ __('suppliers.fields.iban') }}
                        </label>
                        <input type="text" name="iban" id="iban" value="{{ old('iban') }}"
                            class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500"
                            placeholder="CZ0000000000000000000000">
                        <p class="mt-2 text-xs text-gray-500">{{ __('suppliers.hints.iban') }}</p>
                        @error('iban')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- SWIFT -->
                    <div>
                        <label for="swift" class="block text-base font-medium text-gray-900 dark:text-white mb-2">
                            {{ __('suppliers.fields.swift') }}
                        </label>
                        <input type="text" name="swift" id="swift" value="{{ old('swift') }}"
                            class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500"
                            placeholder="AAAACZPP">
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">{{ __('suppliers.hints.swift') }}</p>
                        @error('swift')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 3: Account Security -->
    <div
        class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-20 dark:shadow-gray-900/30 dark:bg-gray-800">
        <div class="p-6">
            <h2 class="text-2xl font-medium text-gray-900 dark:text-white mb-4">{{ __('users.sections.security') }}</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Password -->
                @php $field = collect($passwordFields)->firstWhere('name', 'password') ?? []; @endphp
                <div class="mb-5">
                    <label for="password" class="block text-base font-medium text-gray-900 dark:text-white mb-2">
                        {{ __('users.fields.password') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="password" id="password" required
                        placeholder="{{ $field['placeholder'] ?? '' }}"
                        class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500">
                    @if(isset($field['hint']))
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $field['hint'] }}</p>
                    @endif
                    @error('password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Confirmation -->
                @php $field = collect($passwordFields)->firstWhere('name', 'password_confirmation') ?? []; @endphp
                <div class="mb-5">
                    <label for="password_confirmation"
                        class="block text-base font-medium text-gray-900 dark:text-white mb-2">
                        {{ __('users.fields.password_confirmation') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                        placeholder="{{ $field['placeholder'] ?? '' }}"
                        class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600 dark:focus:border-indigo-500 dark:focus:ring-indigo-500">
                    @if(isset($field['hint']))
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $field['hint'] }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Registration Buttons -->
    <div class="flex justify-between mb-20 md:mb-10">
        <a href="{{ route('frontend.login', ['locale' => app()->getLocale()]) }}"
            class="inline-flex justify-center py-6 px-12 border border-white dark:border-gray-700 shadow-xl shadow-indigo-600/30 dark:shadow-gray-900/30 dark:shadow-md text-lg font-semibold rounded-sm text-gray-700 dark:text-white hover:text-gray-200 dark:hover:text-white bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
            {{ __('users.messages.login_prompt') }}
        </a>
        <button type="submit"
            class="inline-flex items-center px-12 py-6 border border-transparent rounded-sm dark:shadow-md shadow-xl shadow-indigo-600/30 dark:shadow-gray-900/30 text-lg font-semibold text-white hover:text-white bg-[#490BF4] hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 cursor-pointer transition-colors duration-200">
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
