@extends('layouts.frontend')

@section('content')
<div class="flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div>
            <h1 class="text-center text-3xl tracking-tight text-amber-600">{{ __('users.titles.login') }}</h1>
            <p class="mt-2 text-center text-sm text-gray-600">
                {{ config('app.name', 'Laravel') }} - {{ __('users.titles.system_name') }}
            </p>
        </div>
        
        @if ($errors->any())
            <div class="rounded-md bg-red-50 p-4 border border-red-400">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">{{ __('users.errors.login_errors') }}</h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif
        
        <form class="mt-8 space-y-6" action="{{ route('login') }}" method="POST">
            @csrf
            
            <div class="rounded-md shadow-sm -space-y-px">
                <div>
                    <label for="email" class="sr-only">{{ __('users.fields.email') }}</label>
                    <input id="email" name="email" type="email" autocomplete="email" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                           placeholder="{{ __('users.fields.email_placeholder') }}" value="{{ old('email') }}">
                </div>
                <div>
                    <label for="password" class="sr-only">{{ __('users.fields.password') }}</label>
                    <input id="password" name="password" type="password" autocomplete="current-password" required 
                           class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                           placeholder="{{ __('users.fields.password_placeholder') }}">
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <input id="remember" name="remember" type="checkbox" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                    <label for="remember" class="ml-2 block text-sm text-gray-900">
                        {{ __('users.fields.remember_me') }}
                    </label>
                </div>

                <div class="text-sm">
                    @if (Route::has('frontend.password.request'))
                    <a href="@localizedRoute('frontend.password.request')" class="font-medium text-indigo-600 hover:text-indigo-500">
                        {{ __('users.actions.forgot_password') }}
                    </a>
                    @endif
                </div>
    
                <div class="text-sm">
                    <span class="text-gray-500">{{ __('users.messages.no_account') }}</span>
                    <a href="@localizedRoute('register')" class="font-medium text-indigo-600 hover:text-indigo-500">
                        {{ __('users.messages.register_prompt') }}
                    </a>
                </div>
            </div>

            <div>
                <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-300 hover:bg-cyan-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 cursor-pointer">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-white-500 group-hover:text-white-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                    </span>
                    {{ __('users.actions.login') }}
                </button>
            </div>

            <!-- set Locale -->
            <input type="hidden" name="lang" value="{{ app()->getLocale() }}">
        </form>
    </div>
</div>
@endsection