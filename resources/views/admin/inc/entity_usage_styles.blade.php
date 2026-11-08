@push('after_styles')
    @once
        <style>
            /* Tabler paints the value of a native progress element with --tblr-primary */
            .entity-usage-ok { --tblr-primary: var(--tblr-success); }
            .entity-usage-near { --tblr-primary: var(--tblr-warning); }
            .entity-usage-full { --tblr-primary: var(--tblr-danger); }
        </style>
    @endonce
@endpush
