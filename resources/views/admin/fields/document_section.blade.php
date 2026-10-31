@include('crud::fields.inc.wrapper_start')
    <h3 class="mb-1 pb-2 border-bottom">{{ $field['label'] }}</h3>
    @if ($field['name'] === 'section_counterparty')
        <p class="text-muted mb-0" data-document-contact-status aria-live="polite"
           data-copy="{{ __('parties.hints.document_contact_loaded') }}"
           data-correction="{{ __('parties.hints.document_contact_correction') }}"
           data-manual="{{ __('parties.hints.document_contact_manual') }}"
           data-loading="{{ __('parties.messages.document_profile_loading') }}"
           data-error="{{ __('parties.messages.document_profile_unavailable') }}"></p>
    @endif
    @if ($field['hint'])
        <p class="text-muted mb-0">{{ $field['hint'] }}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
    @once
        @vite('resources/js/admin/document-form.js')
    @endonce
@endpush
