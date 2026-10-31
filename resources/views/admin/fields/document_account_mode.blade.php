@include('crud::fields.inc.wrapper_start')
    <p class="mb-0 text-muted" data-document-account-mode aria-live="polite"
       data-selected="{{ __('parties.hints.document_selected_account') }}"
       data-one-off="{{ __('parties.hints.document_one_off_account') }}"></p>
@include('crud::fields.inc.wrapper_end')
