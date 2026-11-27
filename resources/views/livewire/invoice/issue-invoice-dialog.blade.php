@use('App\Domain\User\Enums\LimitedEntity')
<div>
    @if($invoiceId !== null)
    <div x-data x-on:keydown.escape.window="$wire.close()" class="fixed inset-0 z-50 overflow-y-auto" role="dialog"
        aria-modal="true" aria-labelledby="issue-dialog-title">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-black/50" wire:click="close" aria-hidden="true"></div>
            <form wire:submit="issue"
                class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl w-full max-w-lg p-6 space-y-4 text-left">
                <h3 id="issue-dialog-title" class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('invoices.titles.issue') }}</h3>
                <p class="text-sm text-gray-600 dark:text-gray-400 whitespace-normal">{{ __('invoices.hints.issue') }}</p>

                <x-limit-exceeded :message="$limitMessage" />
                <x-entity-usage :entity="LimitedEntity::INVOICE" />

                <div>
                    <label for="issue-number" class="block text-base font-medium text-gray-900 dark:text-white mb-2">
                        {{ __('invoices.fields.invoice_vs') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="issue-number" wire:model="number" required
                        class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
                    @error('number')
                    <p class="mt-2 text-sm text-red-600 whitespace-normal">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="issue-date" class="block text-base font-medium text-gray-900 dark:text-white mb-2">
                        {{ __('invoices.fields.issue_date') }} <span class="text-red-500">*</span>
                    </label>
                    <input type="date" id="issue-date" wire:model="issueDate" required
                        class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
                    @error('issueDate')
                    <p class="mt-2 text-sm text-red-600 whitespace-normal">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="issue-tax-point-date" class="block text-base font-medium text-gray-900 dark:text-white mb-2">
                        {{ __('invoices.fields.tax_point_date') }}
                    </label>
                    <input type="date" id="issue-tax-point-date" wire:model="taxPointDate"
                        class="form-input block w-full rounded-sm border-blue-100 focus:border-indigo-600 focus:ring-indigo-600 text-base px-4 py-2 bg-blue-50 dark:bg-gray-700 dark:text-gray-200 dark:border-gray-600">
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 whitespace-normal">{{ __('invoices.hints.issue_tax_point_date') }}</p>
                    @error('taxPointDate')
                    <p class="mt-2 text-sm text-red-600 whitespace-normal">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" wire:click="close"
                        class="px-4 py-2 text-gray-600 dark:text-gray-400 border border-gray-300 dark:border-gray-500 rounded-md hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer">
                        {{ __('general.actions.cancel') }}
                    </button>
                    <button type="submit" wire:loading.attr="disabled"
                        class="px-4 py-2 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 cursor-pointer">
                        {{ __('invoices.actions.issue') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>
