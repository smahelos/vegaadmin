@extends('layouts.frontend')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h1 class="text-3xl text-amber-600">{{ __('users.titles.edit_profile') }}</h1>
    <div class="space-x-2">
        <a href="{{ route('frontend.dashboard', ['locale' => app()->getLocale()]) }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-gray-700 text-sm font-medium transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>{{ __('users.actions.back_to_dashboard') }}
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">

<form method="POST" action="{{ route('frontend.profile.update', ['locale' => app()->getLocale()]) }}">
@csrf
    @method('PUT')
        <!-- Section 1: Basic informations -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg mb-8">
            <div class="p-6">
                <h2 class="text-2xl font-medium text-gray-900 mb-4">{{ __('users.sections.basic_info') }}</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Name -->
                    <div class="mb-5">
                        <label for="name" class="block text-base font-medium text-gray-500 mb-2">
                            {{ __('users.fields.name') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                            name="name" 
                            id="name" 
                            value="{{ old('name', $user->name) }}" 
                            required
                            class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-blue-50">
                        @error('name')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-2 text-xs text-white">x</p>
                    </div>
                    
                    <!-- Email -->
                    <div class="mb-5">
                        <label for="email" class="block text-base font-medium text-gray-500 mb-2">
                            {{ __('users.fields.email') }} <span class="text-red-500">*</span>
                        </label>
                        <input type="email" 
                            name="email" 
                            id="email" 
                            value="{{ old('email', $user->email) }}" 
                            required
                            class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-blue-50">
                        @error('email')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
                
    <!-- Submit -->
    <div class="flex justify-between">
        <a href="{{ route('frontend.dashboard', ['locale' => app()->getLocale()]) }}" class="inline-flex justify-center py-2 px-5 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
        {{ __('users.actions.cancel') }}
        </a>
        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white hover:text-white bg-emerald-500 hover:bg-emerald-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 cursor-pointer">
            <i class="fas fa-save mr-2"></i>
            {{ __('users.actions.save') }}
        </button>
    </div>

    <!-- set Locale -->
    <input type="hidden" name="lang" value="{{ app()->getLocale() }}">
</form>

<form method="POST" action="{{ route('frontend.profile.update.password', ['lang' => app()->getLocale()]) }}">
    @csrf
    @method('PUT')
    <!-- Section 2: Password change -->
    <div class="bg-blue-100 overflow-hidden shadow-sm rounded-lg mb-8">
        <div class="p-6">
            <h2 class="text-2xl font-medium text-gray-900 mb-4">{{ __('users.sections.change_password') }}</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Field for password change from trait -->
                    @foreach($passwordFields as $field)
                        <div class="mb-5">
                            <label for="{{ $field['name'] }}" class="block text-base font-medium text-gray-500 mb-2 h-6">
                                {{ $field['label'] }}
                                @if(isset($field['required']) && $field['required'])
                                    <span class="text-red-500">*</span>
                                @endif
                            </label>
                            <input type="{{ $field['type'] }}" 
                                   name="{{ $field['name'] }}" 
                                   id="{{ $field['name'] }}" 
                                   class="form-input mt-1 block w-full rounded-md border-gray-300 shadow-md focus:border-indigo-500 focus:ring-indigo-500 text-base px-4 py-2 bg-[#FDFDFC]"
                                   @if(isset($field['required']) && $field['required']) required @endif>
                            @if(isset($field['hint']))
                                <p class="mt-2 text-xs text-gray-500">{{ $field['hint'] }}</p>
                            @endif
                            @error($field['name'])
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    @endforeach
            </div>
        </div>
    </div>
    <!-- Submit -->
    <div class="flex justify-end">
        <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-400 hover:bg-cyan-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 cursor-pointer">
            <i class="fas fa-key mr-2"></i>
            {{ __('users.actions.update_password') }}
        </button>
    </div>

    <!-- set Locale -->
    <input type="hidden" name="lang" value="{{ app()->getLocale() }}">
</form>
</div>
@endsection
