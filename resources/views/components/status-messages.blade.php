@props([
    'error' => null,
    'success' => null,
])

@if ($error)
    <div class="mb-4 rounded-sm border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:bg-red-900/30 dark:text-red-200" role="alert">
        {{ $error }}
    </div>
@endif
@if ($success)
    <div class="mb-4 rounded-sm border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:bg-green-900/30 dark:text-green-200" role="status">
        {{ $success }}
    </div>
@endif
