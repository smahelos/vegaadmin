@extends('layouts.frontend')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl text-amber-600">{{ __('clients.titles.index') }}</h1>
    <a href="@localizedRoute('frontend.client.create')" class="inline-flex items-center px-4 py-2 bg-blue-300 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-cyan-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
        <i class="fas fa-plus mr-2"></i> {{ __('clients.actions.new') }}
    </a>
</div>
<div class="grid grid-cols-1 gap-6">
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                @if(isset($clients) && $clients->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('clients.fields.name') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('clients.fields.email') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('clients.fields.phone') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('clients.fields.ico') }}</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('clients.fields.invoices') }}</th>
                                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('clients.fields.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($clients as $client)
                                    @php
                                        $confirmDeleteTxt = __('clients.messages.confirm_delete')
                                    @endphp
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $client->name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $client->email }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $client->phone ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $client->ico ?? '—' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $client->invoices_count ?? $client->invoices->count() }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="@localizedRoute('frontend.client.show', $client->id)" title="{{ __('clients.actions.show') }}" class="text-cyan-600 hover:text-cyan-900 mr-3">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="@localizedRoute('frontend.client.edit', $client->id)" title="{{ __('clients.actions.edit') }}" class="text-yellow-600 hover:text-yellow-900 mr-3">
                                                <i class="fas fa-pencil-alt"></i>
                                            </a>
                                            <a href="@localizedRoute('frontend.invoice.create', ['client_id' => $client->id])" title="{{ __('clients.actions.create') }}" class="text-green-600 hover:text-green-900 mr-3">
                                                <i class="fas fa-file-invoice"></i>
                                            </a>
                                            <a href="#" title="{{ __('clients.actions.delete') }}" class="text-red-600 hover:text-red-900" onclick="event.preventDefault(); if(confirm('@php echo $confirmDeleteTxt; @endphp')) document.getElementById('delete-form-{{ $client->id }}').submit();">
                                                <i class="fas fa-trash-alt"></i>
                                            </a>
                                            <form id="delete-form-{{ $client->id }}" action="{{ route('frontend.client.destroy', $client) }}" method="POST" class="hidden">
                                                @csrf
                                                @method('DELETE')
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($clients instanceof \Illuminate\Pagination\LengthAwarePaginator)
                        <div class="mt-4">
                            {{ $clients->links() }}
                        </div>
                    @endif
                @else
                    <div class="text-center py-10">
                        <div class="text-gray-400 mb-3">
                            <i class="fas fa-users fa-3x"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('clients.empty.title') }}</h3>
                        <p class="text-gray-500 mb-6">{{ __('clients.empty.message') }}</p>
                        <a href="@localizedRoute('frontend.client.create')" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <i class="fas fa-plus mr-2"></i> {{ __('clients.actions.new') }}
                        </a>
                    </div>
                @endif
            </div>
        </div>
</div>

@if(isset($livewire) && $livewire)
    @livewire('clients-table')
@endif
@endsection