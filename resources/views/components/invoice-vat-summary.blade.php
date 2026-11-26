{{-- The summary by VAT rate of the edited lines; resources/js/invoice-item-manager.js fills the rows and hides it for a non-payer. --}}
<div id="invoice-vat-summary" hidden>
    <div class="flex justify-end mt-6">
        <table class="text-base text-gray-900 dark:text-white">
            <caption class="pb-2 text-left text-lg font-medium">{{ __('invoices.vat_lines.titles.summary') }}</caption>
            <thead>
                <tr class="text-sm text-gray-600 dark:text-gray-400">
                    <th scope="col" class="py-1 pr-4 text-left font-medium">{{ __('invoices.vat_lines.fields.rate') }}</th>
                    <th scope="col" class="py-1 pl-4 text-right font-medium">{{ __('invoices.vat_lines.fields.base') }}</th>
                    <th scope="col" class="py-1 pl-4 text-right font-medium">{{ __('invoices.vat_lines.fields.vat') }}</th>
                    <th scope="col" class="py-1 pl-4 text-right font-medium">{{ __('invoices.vat_lines.fields.total') }}</th>
                </tr>
            </thead>
            <tbody id="invoice-vat-summary-rows"></tbody>
        </table>
    </div>
</div>
