<div>
    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
        @forelse($parties as $party)
            <li class="px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700">
                <a href="{{ route('frontend.' . $resource . '.show', [$kind->value => $party->id->value, 'locale' => app()->getLocale()]) }}" class="flex justify-between items-center gap-3">
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white break-words">{{ $party->profile->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-300 break-words">{{ $party->profile->email ?? __('dashboard.status.no_email') }}</p>
                    </div>
                    <i class="fas fa-chevron-right text-gray-400" aria-hidden="true"></i>
                </a>
            </li>
        @empty
            <li class="p-4 text-center text-gray-500 dark:text-gray-300">{{ __('general.empty.no_results') }}</li>
        @endforelse
    </ul>
    <div class="bg-gray-50 dark:bg-gray-800 px-4 py-3 border-t border-gray-200 dark:border-gray-700">
        <a href="{{ route('frontend.' . $resource . '.index', ['locale' => app()->getLocale()]) }}" class="text-sm font-medium text-indigo-600 dark:text-indigo-400">
            {{ __('general.actions.view_all') }}
        </a>
    </div>
</div>
