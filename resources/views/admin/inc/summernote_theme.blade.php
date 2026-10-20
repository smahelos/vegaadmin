{{--
    Tabler colours for Backpack's summernote field (summernote-lite hard-codes light colours), so the editor
    follows the admin light/dark theme (data-bs-theme). Dialogs, dropdowns and popovers are appended to <body>,
    so the rules are not scoped to the field.
--}}
@push('crud_fields_styles')
    @once
        <style>
            .note-editor.note-frame { border-color: var(--tblr-border-color); color: var(--tblr-body-color); }
            .note-editor.note-frame .note-editing-area .note-editable { background-color: var(--tblr-bg-forms); color: var(--tblr-body-color); }
            .note-editor.note-frame .note-editing-area .note-editable a { color: var(--tblr-link-color); }
            .note-editor.note-frame .note-statusbar,
            .note-toolbar { background-color: var(--tblr-bg-surface-secondary); border-color: var(--tblr-border-color); color: var(--tblr-body-color); }
            .note-btn,
            .note-btn:disabled { background-color: var(--tblr-bg-surface); border-color: var(--tblr-border-color); color: var(--tblr-body-color); }
            .note-btn:hover, .note-btn:focus, .note-btn.focus, .note-btn:active, .note-btn.active { background-color: var(--tblr-active-bg); border-color: var(--tblr-border-color); color: var(--tblr-body-color); }
            .note-dropdown-menu,
            .note-popover, .note-popover .note-popover-content,
            .note-modal-content { background-color: var(--tblr-bg-surface); border-color: var(--tblr-border-color); color: var(--tblr-body-color); }
            .note-dropdown-item, .note-dropdown-item > * { color: var(--tblr-body-color); }
            .note-dropdown-item:hover, .note-modal .note-modal-body .help-list-item:hover { background-color: var(--tblr-active-bg); }
            .note-modal-header { border-color: var(--tblr-border-color); }
            .note-modal-title, .note-modal .note-help-block, .note-form-label { color: var(--tblr-body-color); }
        </style>
    @endonce
@endpush
