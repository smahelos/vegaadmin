@include('crud::fields.inc.wrapper_start')
    <label>{{ __('parties.fields.supplier_logo') }}</label>
    <div data-document-logo data-logo-source="{{ $field['source'] }}" data-stored-logo="{{ $field['logo_url'] ?? '' }}">
        <img src="{{ $field['logo_url'] ?? '' }}" alt="{{ __('parties.fields.supplier_logo') }}" class="img-fluid mb-2" width="160" @if(empty($field['logo_url'])) hidden @endif>
        <p class="text-muted mb-2" data-no-logo @if(!empty($field['logo_url'])) hidden @endif>{{ __('parties.messages.no_logo') }}</p>
        <p class="text-muted mb-2">{{ __($field['source'] === 'business' ? 'parties.hints.invoice_supplier_logo' : 'parties.hints.expense_supplier_logo') }}</p>
        @if ($field['source'] === 'contact')
            <div data-one-off-logo>
                <input type="file" name="supplier_logo" accept="image/png,image/jpeg,image/webp" class="form-control">
                <label class="form-check mt-2">
                    <input type="checkbox" name="remove_supplier_logo" value="1" class="form-check-input">
                    <span class="form-check-label">{{ __('parties.actions.remove_logo') }}</span>
                </label>
            </div>
        @endif
    </div>
@include('crud::fields.inc.wrapper_end')
