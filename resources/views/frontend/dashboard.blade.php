@extends('layouts.frontend')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h1 class="main_title text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-4 leading-tight pt-3">{{ __('dashboard.title') }}</h1>
</div>
<div class="grid grid-cols-1 gap-6 dashboard-stats-container">
    <div class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-8 dark:shadow-gray-900/30 dark:bg-gray-800">
        <div class="p-3">
            <div class="p-3">

                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                    <!-- Dashboard cards with overview -->
                    <div class="bg-blue-50 dark:bg-blue-500 rounded-sm p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-500 dark:bg-blue-700 text-white mr-4">
                                <i class="fas fa-file-invoice-dollar text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-white font-medium">{{ __('dashboard.cards.invoices_count') }}
                                </p>
                                <p class="text-3xl font-semibold text-gray-800">{{ $invoiceCount }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-red-50 dark:bg-red-500 rounded-sm p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-green-500 dark:bg-green-700 text-white mr-4">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-white font-medium">{{ __('dashboard.cards.clients_count') }}
                                </p>
                                <p class="text-3xl font-semibold text-gray-800">{{ $clientCount }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-green-50 dark:bg-green-500 rounded-sm p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-orange-500 dark:bg-orange-700 text-white mr-4">
                                <i class="fas fa-users text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-white font-medium">{{ __('dashboard.cards.suppliers_count') }}
                                </p>
                                <p class="text-3xl font-semibold text-gray-800">{{ $suppliersCount }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-purple-50 dark:bg-purple-500 rounded-sm p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-purple-500 dark:bg-purple-700 text-white mr-4">
                                <i class="fas fa-money-bill-wave text-xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500 dark:text-white font-medium">{{ __('dashboard.cards.total_amount') }}
                                </p>
                                <p class="text-1xl font-semibold text-gray-800">{{ number_format($totalAmount, 2, ',', '
                                    ') }} {{ __('general.currency') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                @if(!empty($clients))
                <!-- Statistics filter controls -->
                <div class="rounded-sm mb-6 flex flex-wrap gap-5 items-center">
                    <div class="flex w-full gap-5">

                        <div class="w-full">
                            <label for="stats-client-filter" class="block text-base font-semibold text-gray-900 dark:text-white mb-1">{{
                                __('dashboard.filters.clients') }}</label>
                            <select id="stats-client-filter"
                                class="mt-1 block w-full rounded-sm border-blue-100 dark:border-gray-700 focus:border-indigo-600 focus:ring-indigo-600 text-base dark:text-white bg-blue-50 dark:bg-gray-600 px-1 py-1"
                                multiple>
                                @foreach($clients as $client)
                                <option class="@if($loop->iteration % 2 == 0)bg-blue-50 dark:bg-gray-600 @else bg-white dark:bg-gray-700 @endif px-2 py-1" value="{{ $client->clientId }}">{{ $client->clientName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="grid md:grid-cols-3 min-w-2/3 bg-red-100 dark:bg-pink-700 rounded-sm p-5">
                            <div class="flex gap-2 items-center cols-span-1">
                                <div>
                                    <label for="stats-timerange" class="block text-base font-semibold text-gray-900 dark:text-white mb-1">{{
                                        __('dashboard.filters.date_range') }}</label>
                                    <select id="stats-timerange"
                                        class="form-input mt-1 block w-full rounded-sm border-blue-100 dark:border-gray-600 focus:border-indigo-600 focus:ring-indigo-600 text-base dark:text-white px-4 py-2 bg-blue-50 dark:bg-gray-700">
                                        <option value="year">{{ __('dashboard.filters.last_year') }}</option>
                                        <option value="6month">{{ __('dashboard.filters.last_6_months') }}</option>
                                        <option value="quarter">{{ __('dashboard.filters.last_quarter') }}</option>
                                        <option value="month">{{ __('dashboard.filters.last_month') }}</option>
                                        <option value="custom">{{ __('dashboard.filters.custom_range') }}</option>
                                    </select>
                                </div>
                            </div>

                            <div class="flex gap-2 items-center cols-span-2 custom-date-range">
                                <div class="w-full md:min-w-2/2">
                                    <label for="stats-date-from" class="block text-base font-semibold text-gray-700 dark:text-white mb-1">{{
                                        __('dashboard.filters.date_from') }}</label>
                                    <input type="date" id="stats-date-from"
                                        class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-gray-200 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-500 dark:focus:border-indigo-500 dark:focus:ring-indigo-500" readonly>
                                </div>
                                <div class="w-full md:min-w-2/2">
                                    <label for="stats-date-to" class="block text-base font-semibold text-gray-700 dark:text-white mb-1">{{
                                        __('dashboard.filters.date_to') }}</label>
                                    <input type="date" id="stats-date-to"
                                        class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-gray-200 dark:bg-gray-700 dark:text-gray-400 dark:border-gray-500 dark:focus:border-indigo-500 dark:focus:ring-indigo-500" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-8 dark:shadow-gray-900/30 dark:bg-gray-800">
        <div class="p-3">
            <div class="">
                @if(!empty($monthlyStats))
                    <!-- Store monthly stats data for JavaScript -->
                    <div id="monthly-stats-data" class="hidden" data-stats="{{ json_encode($monthlyStats ?? []) }}"></div>

                    <!-- Main revenue chart (enhanced) -->
                    <div class="p-5 mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b-1 pb-2 border-gray-200 dark:border-gray-700">{{ __('dashboard.monthly_invoices') }}</h3>
                        <div class="h-64">
                            <canvas id="invoicesChart"></canvas>
                        </div>
                    </div>
                @endif

                <!-- Invoice status and payment method charts -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Invoice status distribution -->
                    <div class="p-5">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b-1 pb-2 border-gray-200 dark:border-gray-700">{{ __('dashboard.charts.invoice_status') }}
                        </h3>
                        <div class="h-64">
                            <canvas id="invoiceStatusChart"></canvas>
                        </div>
                    </div>

                    <!-- Payment method distribution -->
                    <div class="p-5">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b-1 pb-2 border-gray-200 dark:border-gray-700">{{ __('dashboard.charts.payment_methods') }}
                        </h3>
                        <div class="h-64">
                            <canvas id="paymentMethodChart"></canvas>
                        </div>
                    </div>
                </div>

                <!-- Revenue by client and Revenue vs Expenses -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Revenue by client -->
                    <div class="p-5">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b-1 pb-2 border-gray-200 dark:border-gray-700">{{ __('dashboard.charts.revenue_by_client')
                            }}</h3>
                        <div class="h-64">
                            <canvas id="revenueByClientChart"></canvas>
                        </div>
                    </div>

                    <!-- Revenue vs Expenses trend -->
                    <div class="p-5">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b-1 pb-2 border-gray-200 dark:border-gray-700">{{ __('dashboard.charts.revenue_vs_expenses') }}
                        </h3>
                        <div class="h-64">
                            <canvas id="revenueExpensesChart"></canvas>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <div class="bg-white overflow-hidden shadow-2xl dark:shadow-md shadow-indigo-600/30 rounded-md mb-10 dark:shadow-gray-900/30 dark:bg-gray-800">
        <div class="p-3">
            <div class="">

                <!-- Latest invoices -->
                <div class="mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b-1 pb-2 border-gray-200 dark:border-gray-700">{{ __('dashboard.recent_invoices') }}</h3>
                    <div class="overflow-hidden">
                        <!-- Livewire component - Latest invoices -->
                        @livewire('invoice.invoice-list-recent')
                    </div>
                </div>

                <!-- Latest clients and suppliers -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Latest clients -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b-1 pb-2 border-gray-200 dark:border-gray-700">{{ __('dashboard.recent_clients') }}</h3>
                        <div class="rounded-sm overflow-hidden">
                            <!-- Livewire component - Latest clients -->
                            @livewire('party.client-list-latest')
                        </div>
                    </div>

                    <!-- Latest suppliers -->
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 border-b-1 pb-2 border-gray-200 dark:border-gray-700">{{ __('dashboard.recent_suppliers') }}</h3>
                        <div class="rounded-sm overflow-hidden">
                            <!-- Livewire component - Latest suppliers -->
                            @livewire('party.supplier-list-latest')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Make translations available for the dashboard statistics module
    window.translations = window.translations || {};
    window.translations.dashboard = window.translations.dashboard || {};
    window.translations.dashboard.charts = {
        billedAmount: "{{ __('dashboard.charts.billed_currency') }}",
        paidAmount: "{{ __('dashboard.charts.paid_currency') }}",
        amount: "{{ __('dashboard.charts.amount') }}",
        revenue: "{{ __('dashboard.charts.revenue') }}",
        expenses: "{{ __('dashboard.charts.expenses') }}",
        paid: "{{ __('dashboard.status.paid') }}",
        partiallyPaid: "{{ __('dashboard.status.partially-paid') }}",
        pending: "{{ __('dashboard.status.pending') }}",
        overdue: "{{ __('dashboard.status.overdue') }}",
        draft: "{{ __('dashboard.status.draft') }}",
        cancelled: "{{ __('dashboard.status.cancelled') }}",
        noData: "{{ __('dashboard.charts.no_data') }}",
    };

    // Set application currency
    window.appCurrency = "{{ config('app.currency', 'CZK') }}";

    // Initialize date range controls
    document.addEventListener('DOMContentLoaded', function() {
        const timeRangeSelector = document.getElementById('stats-timerange');
        const timeRangeDateFrom = document.getElementById('stats-date-from');
        const timeRangeDateTo = document.getElementById('stats-date-to');
        const customDateRange = document.querySelector('.custom-date-range');

        if (timeRangeSelector && customDateRange) {
            timeRangeSelector.addEventListener('change', function(e) {
                if (e.target.value === 'custom') {
                    //customDateRange.classList.remove('hidden');
                    timeRangeDateFrom.removeAttribute('readonly');
                    timeRangeDateTo.removeAttribute('readonly');

                    // Remove visual indicator classes
                    timeRangeDateFrom.classList.remove('bg-gray-200', 'text-gray-400');
                    timeRangeDateFrom.classList.add('bg-[#FDFDFC]');
                    timeRangeDateTo.classList.remove('bg-gray-200', 'text-gray-400');
                    timeRangeDateTo.classList.add('bg-[#FDFDFC]');
                } else {
                    //customDateRange.classList.add('hidden');
                    timeRangeDateFrom.setAttribute('readonly', true);
                    timeRangeDateTo.setAttribute('readonly', true);

                    // Add visual indicator classes
                    timeRangeDateFrom.classList.add('bg-gray-200', 'text-gray-400');
                    timeRangeDateFrom.classList.remove('bg-[#FDFDFC]');
                    timeRangeDateTo.classList.add('bg-gray-200', 'text-gray-400');
                    timeRangeDateTo.classList.remove('bg-[#FDFDFC]');
                }
            });
        }
    });
</script>
@endpush
