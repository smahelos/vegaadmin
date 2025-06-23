<div>
    @if(isset($clients) && $clients->count() > 0)
        <ul class="divide-y divide-gray-200">
            @foreach($clients as $client)
                <li class="px-4 hover:bg-gray-50">
                    <a href="{{ route('frontend.client.show', ['id' => $client->id, 'locale' => app()->getLocale()]) }}" class="flex justify-between items-center">
                        <div class="w-50">
                            <p class="text-sm font-medium text-gray-900">{{ $client->name }}</p>
                            <p class="text-xs text-gray-500">{{ $client->email ?? __('dashboard.status.no_email') }}</p>
                        </div>
                        <div class="w-40 text-center p-5 bg-yellow-100 text-yellow-800">
                            <p class="">{{ $client->created_at->format('d.m.Y') }}</p>
                        </div>
                        <i class="fas fa-chevron-right text-gray-400 w-10"></i>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 sm:px-6">
            <a href="{{ route('frontend.clients', ['locale' => app()->getLocale()]) }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                {{ __('dashboard.actions.view_all_clients') }} <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
    @else
        <div class="p-4 text-center text-gray-500">
            <p>{{ __('dashboard.status.no_clients') }}</p>
        </div>
    @endif
</div>
