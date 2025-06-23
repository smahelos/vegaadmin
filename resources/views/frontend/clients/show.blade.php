@extends('layouts.frontend')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl text-amber-600"><span class="text-base text-gray-900 font-medium">{{ __('clients.titles.client') }}</span> {{ $client->name }}</h1>
    <div class="flex space-x-4">

        <x-back-button />

        <a href="{{ route('frontend.clients', ['locale' => app()->getLocale()]) }}" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-md text-gray-700 text-sm font-medium transition-colors">
            <i class="fas fa-arrow-left mr-2"></i> {{ __('clients.actions.back_to_list') }}
        </a>
        <a href="{{ route('frontend.client.edit', ['locale' => app()->getLocale(), $client->id]) }}" class="px-4 py-2 bg-green-200 hover:bg-emerald-500 rounded-md text-sm text-gray-700 hover:text-white font-medium transition-colors">
            <i class="fas fa-pencil-alt pr-2"></i> {{ __('clients.actions.edit') }}
        </a>
    </div>
</div>
<!-- Main client information -->
<div class="mb-6">
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Left column: Basic information -->
                <div class="p-6 overflow-hidden shadow-md bg-blue-50 rounded-lg border border-blue-200">
                    <div class="border-b border-blue-200 mb-4 pb-2 flex justify-between items-center">
                        <h2 class="text-xl text-gray-800">{{ __('clients.sections.basic_info') }}</h2>
                        <span class="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full">{{ __('clients.tags.client') }}</span>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex flex-wrap">
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-green-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('clients.fields.name') }}</div>
                                    <div class="font-medium">{{ $client->name }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-green-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('clients.fields.email') }}</div>
                                    <div class="font-medium">{{ $client->email }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-orange-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('clients.fields.phone') }}</div>
                                    <div class="font-medium">{{ $client->phone ?? __('general.empty.not_specified') }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-orange-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('clients.fields.shortcut') }}</div>
                                    <div class="font-medium">{{ $client->shortcut ?? __('general.empty.not_specified') }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-yellow-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('clients.fields.created_at') }}</div>
                                    <div class="font-medium">{{ \Carbon\Carbon::parse($client->created_at)->format(App\Helpers\DateHelper::format()) }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-yellow-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('clients.fields.is_default') }}</div>
                                    <div class="font-medium">
                                        <span class="font-semibold @if($client->is_default > 0)text-green-600 @else text-red-600 @endif">
                                            @if($client->is_default > 0)
                                                {{ __('general.placeholders.yes') }}
                                            @else
                                                {{ __('general.placeholders.no') }}
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        @if(!empty($client->description))
                        <div class="bg-white p-3 rounded-md shadow-sm">
                            <div class="text-xs text-gray-500 mb-1">{{ __('clients.fields.description') }}</div>
                            <div class="text-sm whitespace-pre-line">{{ $client->description }}</div>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Right column: Billing information -->
                <div class="p-6 overflow-hidden shadow-md bg-green-50 rounded-lg border border-green-200">
                    <div class="border-b border-green-200 mb-4 pb-2 flex justify-between items-center">
                        <h2 class="text-xl text-gray-800">{{ __('clients.sections.billing_info') }}</h2>
                        <span class="bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">{{ __('clients.tags.billing') }}</span>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="flex flex-wrap">
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-orange-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('clients.fields.street') }}</div>
                                    <div class="font-medium">{{ $client->street }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-orange-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('clients.fields.city') }}</div>
                                    <div class="font-medium">{{ $client->city }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-red-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('clients.fields.zip') }}</div>
                                    <div class="font-medium">{{ $client->zip }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-red-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('clients.fields.country') }}</div>
                                    <div class="font-medium">{{ $client->country }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-purple-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('clients.fields.ico') }}</div>
                                    <div class="font-medium">{{ $client->ico ?? __('general.empty.not_specified') }}</div>
                                </div>
                            </div>
                            <div class="w-full md:w-1/2 mb-4">
                                <div class="border-l-4 border-purple-500 pl-3 py-1">
                                    <div class="text-xs text-gray-500">{{ __('clients.fields.dic') }}</div>
                                    <div class="font-medium">{{ $client->dic ?? __('general.empty.not_specified') }}</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Display delivery address if different from billing -->
                        @if($client->has_delivery_address)
                        <div class="bg-white p-3 rounded-md shadow-sm">
                            <div class="text-sm font-medium mb-2">{{ __('clients.sections.delivery_address') }}</div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <div class="text-xs text-gray-500">{{ __('clients.fields.delivery_street') }}</div>
                                    <div class="text-sm">{{ $client->delivery_street }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">{{ __('clients.fields.delivery_city') }}</div>
                                    <div class="text-sm">{{ $client->delivery_city }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">{{ __('clients.fields.delivery_zip') }}</div>
                                    <div class="text-sm">{{ $client->delivery_zip }}</div>
                                </div>
                                <div>
                                    <div class="text-xs text-gray-500">{{ __('clients.fields.delivery_country') }}</div>
                                    <div class="text-sm">{{ $client->delivery_country }}</div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
    </div>
</div>

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
    <div class="p-6">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-xl font-medium text-gray-900">{{ __('clients.sections.client_invoices') }}</h2>
            <a href="{{ route('frontend.invoice.create', ['client_id' => $client->id, 'locale' => app()->getLocale()]) }}" class="px-4 py-2 bg-blue-300 hover:bg-cyan-600 rounded-md text-gray-700 hover:text-white text-sm font-medium transition-colors">
                <i class="fas fa-plus mr-2"></i> {{ __('invoices.actions.create') }}
            </a>
        </div>
        <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('invoices.fields.invoice_vs') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('invoices.fields.issue_date') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('invoices.fields.due_date') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('invoices.fields.payment_amount') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('invoices.fields.status') }}
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    {{ __('general.actions.actons') }}
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($invoices as $invoice)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $invoice->invoice_vs }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($invoice->issue_date)->format(App\Helpers\DateHelper::format()) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ \Carbon\Carbon::parse($invoice->issue_date)->addDays((int)$invoice->due_in)->format(App\Helpers\DateHelper::format()) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ number_format($invoice->payment_amount, 2, ',', ' ') }} {{ $invoice->payment_currency }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        @if($invoice->paymentStatus && $invoice->paymentStatus->slug)
                                            {{ $invoice->status_color_class }}">
                                        @else
                                            bg-gray-100 text-gray-800">
                                        @endif
                                        @if($invoice->paymentStatus)
                                            {{ $invoice->payment_status_name }}
                                        @else
                                            {{ __('invoices.status.unknown') }}
                                        @endif
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('frontend.invoice.show', ['locale' => app()->getLocale(), 'id' => $invoice->id]) }}" class="text-cyan-600 hover:text-cyan-900 mr-3">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('frontend.invoice.edit', ['locale' => app()->getLocale(), 'id' => $invoice->id]) }}" class="text-yellow-600 hover:text-yellow-900 mr-3">
                                        <i class="fas fa-pencil-alt"></i>
                                    </a>
                                    <a href="{{ route('frontend.invoice.download', ['locale' => app()->getLocale(), 'id' => $invoice->id]) }}" class="text-green-600 hover:text-green-900">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
        </div>
    </div>
</div>
@endsection
