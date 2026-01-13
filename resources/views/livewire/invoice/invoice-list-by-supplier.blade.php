<div>
    @if(isset($errorMessage))
    <div class="bg-red-50 dark:bg-red-700 border border-red-200 text-red-700 dark:text-red-200 px-4 py-3 rounded relative mb-4"
        role="alert">
        <span class="block sm:inline">{{ $errorMessage }}</span>
    </div>
    @endif

    @if(isset($invoices) && $invoices->count() > 0)
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-white dark:bg-gray-800">
                <tr>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-[#490BF4] dark:text-indigo-400 uppercase tracking-wider">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('invoice_vs')">
                            {{ __('invoices.fields.invoice_vs') }}
                            @if($orderBy === 'invoice_vs')
                            <span class="ml-1">
                                @if($orderAsc)
                                <i class="fas fa-sort-up text-green-600"></i>
                                @else
                                <i class="fas fa-sort-down text-green-600"></i>
                                @endif
                            </span>
                            @else
                            <span class="ml-1"><i class="fas fa-sort text-gray-400"></i></span>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-[#490BF4] dark:text-indigo-400 uppercase tracking-wider">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('issue_date')">
                            {{ __('invoices.fields.issue_date') }}
                            @if($orderBy === 'issue_date')
                            <span class="ml-1">
                                @if($orderAsc)
                                <i class="fas fa-sort-up text-green-600"></i>
                                @else
                                <i class="fas fa-sort-down text-green-600"></i>
                                @endif
                            </span>
                            @else
                            <span class="ml-1"><i class="fas fa-sort text-gray-400"></i></span>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-[#490BF4] dark:text-indigo-400 uppercase tracking-wider">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('due_date')">
                            {{ __('invoices.fields.due_date') }}
                            @if($orderBy === 'due_date')
                            <span class="ml-1">
                                @if($orderAsc)
                                <i class="fas fa-sort-up text-green-600"></i>
                                @else
                                <i class="fas fa-sort-down text-green-600"></i>
                                @endif
                            </span>
                            @else
                            <span class="ml-1"><i class="fas fa-sort text-gray-400"></i></span>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-[#490BF4] dark:text-indigo-400 uppercase tracking-wider">
                        <div class="flex items-center cursor-pointer" wire:click="sortBy('payment_amount')">
                            {{ __('invoices.fields.payment_amount') }}
                            @if($orderBy === 'payment_amount')
                            <span class="ml-1">
                                @if($orderAsc)
                                <i class="fas fa-sort-up text-green-600"></i>
                                @else
                                <i class="fas fa-sort-down text-green-600"></i>
                                @endif
                            </span>
                            @else
                            <span class="ml-1"><i class="fas fa-sort text-gray-400"></i></span>
                            @endif
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-[#490BF4] dark:text-indigo-400 uppercase tracking-wider">
                        {{ __('invoices.fields.status') }}
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-right text-xs font-bold text-green-600 uppercase tracking-wider">
                        {{ __('invoices.fields.actions') }}
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($invoices as $invoice)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                        {{ $invoice->invoice_vs }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{
                        \Carbon\Carbon::parse($invoice->issue_date)->format(\App\Infrastructure\Shared\Support\DateHelper::format())
                        }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{
                        \Carbon\Carbon::parse($invoice->issue_date)->addDays((int)$invoice->due_in)->format(\App\Infrastructure\Shared\Support\DateHelper::format())
                        }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ number_format($invoice->payment_amount, 2, ',', ' ') }} {{ $invoice->payment_currency }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm
                        @if($invoice->due_date && $invoice->due_date->isPast() && (!$invoice->paymentStatus || $invoice->payment_status_slug !== 'paid'))
                            text-red-600 font-medium bg-red-100 dark:text-white dark:bg-red-900">
                        @else
                        text-gray-500 dark:text-gray-400">
                        @endif
                        @if($invoice->paymentStatus)
                        {{ $invoice->payment_status_name }}
                        @else
                        {{ __('invoices.status.unknown') }}
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('frontend.invoice.show', ['locale' => app()->getLocale(), 'id' => $invoice->id]) }}"
                            class="text-indigo-500 hover:text-[#490BF4] mr-3 transition-colors"
                            title="{{ __('invoices.actions.view') }}">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('frontend.invoice.edit', ['locale' => app()->getLocale(), 'id' => $invoice->id]) }}"
                            class="text-yellow-600 hover:text-yellow-900 mr-3 transition-colors"
                            title="{{ __('invoices.actions.edit') }}">
                            <i class="fas fa-pencil-alt"></i>
                        </a>
                        <a href="{{ route('frontend.invoice.download', ['locale' => app()->getLocale(), 'id' => $invoice->id]) }}"
                            class="text-green-600 hover:text-green-900 transition-colors"
                            title="{{ __('invoices.actions.download') }}">
                            <i class="fas fa-download"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($invoices->hasPages())
    <div class="mt-4">
        {{ $invoices->links() }}
    </div>
    @endif
    @else
    <div class="text-center py-8">
        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
            <i class="fas fa-file-invoice text-gray-400 text-4xl mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">{{ __('invoices.messages.no_invoices') }}
            </h3>
            <p class="text-gray-500 dark:text-gray-400 mb-4">{{ __('invoices.messages.no_invoices_description') }}</p>
            @if($supplier)
            <a href="{{ route('frontend.invoice.create', ['supplier_id' => $supplier->id, 'locale' => app()->getLocale()]) }}"
                class="inline-flex items-center px-4 py-2 bg-indigo-500 hover:bg-[#490BF4] border border-transparent rounded-sm font-semibold text-xs text-white uppercase tracking-widest active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition ease-in-out duration-150">
                <i class="fas fa-plus mr-2"></i>
                {{ __('invoices.actions.create') }}
            </a>
            @endif
        </div>
    </div>
    @endif
</div>
