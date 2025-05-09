@extends('layouts.frontend')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl text-amber-600">{{ __('dashboard.title') }}</h1>
</div>
<div class="grid grid-cols-1 gap-6">
    <div class="bg-white overflow-hidden shadow-sm rounded-lg">
        <div class="p-6">
            <div class="p-6 bg-white border-b border-gray-200">
                
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <!-- Dashboard cards with overview -->
                    <div class="bg-blue-50 rounded-lg p-6 shadow-sm">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-500 text-white mr-4">
                                <i class="fas fa-file-invoice-dollar text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-medium">{{ __('dashboard.cards.invoices_count') }}</p>
                                <p class="text-3xl font-semibold text-gray-800">{{ $invoiceCount }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-green-50 rounded-lg p-6 shadow-sm">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-500 text-white mr-4">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-medium">{{ __('dashboard.cards.clients_count') }}</p>
                                <p class="text-3xl font-semibold text-gray-800">{{ $clientCount }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-green-50 rounded-lg p-6 shadow-sm">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-orange-500 text-white mr-4">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-medium">{{ __('dashboard.cards.suppliers_count') }}</p>
                                <p class="text-3xl font-semibold text-gray-800">{{ $suppliersCount }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-purple-50 rounded-lg p-6 shadow-sm">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-500 text-white mr-4">
                                <i class="fas fa-money-bill-wave text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 font-medium">{{ __('dashboard.cards.total_amount') }}</p>
                                <p class="text-1xl font-semibold text-gray-800">{{ number_format($totalAmount, 2, ',', ' ') }} {{ __('general.currency') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graph of monthly statistics -->
                <div class="bg-white rounded-lg shadow p-5 mb-8">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('dashboard.monthly_invoices') }}</h3>
                    <div class="h-64">
                        <canvas id="invoicesChart"></canvas>
                    </div>
                </div>

                <!-- Latest invoices -->
                <div class="mb-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">{{ __('dashboard.recent_invoices') }}</h3>
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            @if(isset($recentInvoices) && $recentInvoices->count() > 0)
                                <ul class="divide-y divide-gray-200">
                                    @foreach($recentInvoices as $invoice)
                                        <li class="px-4 hover:bg-gray-50 bg-{{ $invoice->status_color_class }}-100">
                                            <a href="@localizedRoute('frontend.invoice.show', $invoice->id)" class="flex flex-row items-center">
                                                <div class="basis-3/24">
                                                    <p class="text-sm font-medium text-gray-900">{{ $invoice->invoice_vs }}</p>
                                                </div>
                                                <div class="basis-6/24">
                                                    <p class="text-xs text-gray-500">
                                                        {{ $invoice->client ? $invoice->client->name : __('dashboard.status.unknown_client') }} - 
                                                        {{ number_format($invoice->payment_amount, 0, ',', ' ') }} {{ __('general.currency') }}
                                                    </p>
                                                </div>
                                                <div class="basis-6/24">
                                                    <p class="text-xs text-gray-500">
                                                        @if($invoice->paymentMethod)
                                                            {{ $invoice->paymentMethod->translated_name }}
                                                        @else
                                                            <span class="text-gray-400">{{ __('invoices.placeholders.not_available') }}</span>
                                                        @endif
                                                    </p>
                                                </div>
                                                <div class="basis-6/24">
                                                    <p class="p-3 text-{{ $invoice->status_color_class }}-800">
                                                        {{ $invoice->client ? $invoice->payment_status_name : __('dashboard.status.unknown_status') }}
                                                    </p>
                                                </div>
                                                <div class="basis-2/24 p-4 text-{{ $invoice->status_color_class }}-800
                                                    ">
                                                    <p class="text-xs">
                                                        @if($invoice->due_date)
                                                            {{ $invoice->due_date->format('d.m.Y') }}
                                                        @else
                                                            <span class="text-gray-400">—</span>
                                                        @endif
                                                    </p>
                                                </div>
                                                <div class="basis-1/24 text-right">
                                                    <i class="fas fa-chevron-right text-gray-400"></i>
                                                </div>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 sm:px-6">
                                    <a href="@localizedRoute('frontend.invoices')" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                        {{ __('dashboard.actions.view_all_invoices') }} <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            @else
                                <div class="p-4 text-center text-gray-500">
                                    <p>{{ __('dashboard.status.no_invoices') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                <!-- Latest clients and suppliers -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Latest clients -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-3">{{ __('dashboard.recent_clients') }}</h3>
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            @if(isset($recentClients) && $recentClients->count() > 0)
                                <ul class="divide-y divide-gray-200">
                                    @foreach($recentClients as $client)
                                        <li class="px-4 hover:bg-gray-50">
                                            <a href="@localizedRoute('frontend.client.show', $client->id)" class="flex justify-between items-center">
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
                                    <a href="@localizedRoute('frontend.clients')" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                        {{ __('dashboard.actions.view_all_clients') }} <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            @else
                                <div class="p-4 text-center text-gray-500">
                                    <p>{{ __('dashboard.status.no_clients') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Latest suppliers -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-3">{{ __('dashboard.recent_suppliers') }}</h3>
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            @if(isset($recentSuppliers) && $recentSuppliers->count() > 0)
                                <ul class="divide-y divide-gray-200">
                                    @foreach($recentSuppliers as $supplier)
                                        <li class="px-4 hover:bg-gray-50">
                                            <a href="@localizedRoute('frontend.supplier.show', $supplier->id)" class="flex justify-between items-center">
                                                <div class="w-50">
                                                    <p class="text-sm font-medium text-gray-900">{{ $supplier->name }}</p>
                                                    <p class="text-xs text-gray-500">{{ $supplier->email ?? __('dashboard.status.no_email') }}</p>
                                                </div>
                                                <div class="w-40 text-center p-5 bg-yellow-100 text-yellow-800">
                                                    <p class="">{{ $supplier->created_at->format('d.m.Y') }}</p>
                                                </div>
                                                <i class="fas fa-chevron-right text-gray-400 w-10"></i>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                                <div class="bg-gray-50 px-4 py-3 border-t border-gray-200 sm:px-6">
                                    <a href="@localizedRoute('frontend.suppliers')" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">
                                        {{ __('dashboard.actions.view_all_suppliers') }} <i class="fas fa-arrow-right ml-1"></i>
                                    </a>
                                </div>
                            @else
                                <div class="p-4 text-center text-gray-500">
                                    <p>{{ __('dashboard.status.no_suppliers') }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-center mt-6">
                    <a href="@localizedRoute('frontend.invoices')" class="inline-flex items-center px-4 py-2 bg-blue-300 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-cyan-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <i class="fas fa-list mr-2"></i> {{ __('dashboard.actions.view_all_invoices') }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        try {
            const ctx = document.getElementById('invoicesChart').getContext('2d');
            
            // Graph data
            const monthlyStatsRaw = '{!! addslashes(json_encode($monthlyStats ?? [])) !!}';
            const monthlyStats = JSON.parse(monthlyStatsRaw);
            
            // If not any data, show empty graph
            if (!monthlyStats || monthlyStats.length === 0) {
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ["{{ __('dashboard.charts.no_data') }}"],
                        datasets: [{
                            label: "{{ __('dashboard.charts.billed_currency') }}",
                            data: [0],
                            backgroundColor: 'rgba(209, 213, 219, 0.5)',
                            borderColor: 'rgb(209, 213, 219)',
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        },
                        plugins: {
                            legend: {
                                display: true
                            }
                        }
                    }
                });
                return;
            }
            
            // Process data for the graph
            // Convert month-year string to Date object for sorting
            const labels = monthlyStats.map(item => {
                const [year, month] = item.month.split('-');
                return new Date(year, month - 1).toLocaleDateString('cs-CZ', { month: 'short', year: 'numeric' });
            });
            
            const data = monthlyStats.map(item => item.total);
            
            // Create the chart
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: "{{ __('dashboard.charts.billed_currency') }}",
                        data: data,
                        backgroundColor: 'rgba(79, 70, 229, 0.5)',
                        borderColor: 'rgb(79, 70, 229)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('cs-CZ', { 
                                        style: 'currency', 
                                        currency: 'CZK',
                                        maximumFractionDigits: 0
                                    }).format(value);
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return new Intl.NumberFormat('cs-CZ', { 
                                        style: 'currency', 
                                        currency: 'CZK',
                                        maximumFractionDigits: 0
                                    }).format(context.raw);
                                }
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error while initializin the graph:', error);
        }
    });
</script>
@endpush
