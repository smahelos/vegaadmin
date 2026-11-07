@props([
    'message' => null,
])

@if ($message)
    <div class="mb-6 rounded-sm border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/30 dark:text-red-200" role="alert">
        {{ $message }}
        <a href="{{ route('subscriptions.index', ['locale' => App::getLocale()]) }}" class="font-medium underline">{{ __('subscriptions.actions.view_plans') }}</a>
    </div>
@endif
