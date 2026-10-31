@include('crud::fields.inc.wrapper_start')
<div data-invoice-items data-items="{{ json_encode($field['items']) }}"
     data-catalog-url="{{ route('admin.invoice-items.catalog') }}"
     data-preview-url="{{ route('admin.invoice-items.preview') }}"
     data-loading="{{ __('invoices.items.loading') }}"
     data-error="{{ __('invoices.items.error') }}"
     data-empty="{{ __('invoices.items.empty_catalog') }}"
     data-owner-changed="{{ __('invoices.items.owner_changed') }}"
     data-locale="{{ app()->getLocale() }}">
    <h3 class="mb-2">{{ $field['label'] }}</h3>
    <p class="text-muted">{{ __('invoices.items.hint') }}</p>
    <input type="hidden" name="invoice-products" value="{{ json_encode($field['items']) }}">
    @foreach ($errors->getMessages() as $key => $messages)
        @if (str_starts_with($key, 'invoice-products'))
            @foreach ($messages as $message)
                <p class="text-danger">{{ $message }}</p>
            @endforeach
        @endif
    @endforeach
    <div class="row g-2 mb-3">
        <label class="col-12 col-md-4">{{ __('invoices.items.search') }}
            <input type="search" class="form-control" data-catalog-search maxlength="255" autocomplete="off">
        </label>
        <label class="col-12 col-md-5">{{ __('invoices.fields.item_product') }}
            <select class="form-select" data-catalog-select></select>
        </label>
        <div class="col-12 col-md-3 d-flex align-items-end">
            <button type="button" class="btn btn-outline-primary w-100" data-add-catalog>{{ __('invoices.items.add_catalog') }}</button>
        </div>
    </div>
    <p class="text-muted" data-catalog-status role="status"></p>
    <div data-item-rows></div>
    <button type="button" class="btn btn-outline-primary mb-3" data-add-custom>{{ __('invoices.items.add_custom') }}</button>
    <div class="alert alert-secondary" data-item-totals hidden>
        <dl class="row mb-0">
            <dt class="col-7">{{ __('invoices.items.subtotal') }}</dt><dd class="col-5 text-end" data-subtotal></dd>
            <dt class="col-7">{{ __('invoices.labels.total_tax') }}</dt><dd class="col-5 text-end" data-tax></dd>
            <dt class="col-7">{{ __('invoices.fields.payment_amount') }}</dt><dd class="col-5 text-end fw-bold" data-total></dd>
        </dl>
    </div>
    <p data-items-status class="text-muted" role="status"></p>
    <template data-item-template>
        <div class="card mb-3" data-item-row>
            <div class="card-body row g-2">
                <input type="hidden" data-key="product_id">
                <label class="col-12 col-md-8">{{ __('invoices.fields.item_name') }}
                    <input class="form-control" data-key="name" required maxlength="255">
                </label>
                <div class="col-12 col-md-4 d-flex align-items-end justify-content-end">
                    <button type="button" class="btn btn-outline-danger" data-remove-item>{{ __('invoices.items.remove') }}</button>
                </div>
                <label class="col-6 col-md-2">{{ __('invoices.fields.item_quantity') }}
                    <input type="number" class="form-control" data-key="quantity" min="0.001" max="99999.999" step="0.001" required>
                </label>
                <label class="col-6 col-md-2">{{ __('invoices.fields.item_unit') }}
                    <select class="form-select" data-key="unit">
                        @foreach ($field['units'] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="col-6 col-md-3">{{ __('invoices.fields.item_price') }}
                    <input type="number" class="form-control" data-key="price" min="0" max="99999999.99" step="0.01" required>
                </label>
                <label class="col-6 col-md-2">{{ __('invoices.fields.item_currency') }}
                    <select class="form-select" data-key="currency">
                        @foreach ($field['currencies'] as $currency)
                            <option value="{{ $currency }}">{{ $currency }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="col-12 col-md-3">{{ __('invoices.fields.item_tax_rate') }}
                    <select class="form-select" data-key="tax_rate">
                        @foreach ($field['tax_rates'] as $rate)
                            <option value="{{ $rate }}">{{ $rate }} %</option>
                        @endforeach
                    </select>
                </label>
                <label class="col-12 col-md-4">{{ __('invoices.fields.item_category') }}
                    <input class="form-control" data-key="category" maxlength="255">
                </label>
                <label class="col-12 col-md-8">{{ __('invoices.fields.item_description') }}
                    <input class="form-control" data-key="description" maxlength="255">
                </label>
            </div>
        </div>
    </template>
</div>
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
    @once
        @vite('resources/js/admin/invoice-items.js')
    @endonce
@endpush
