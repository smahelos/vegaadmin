@use('App\Application\Invoice\Queries\GetIssuedInvoicePdf')
{{-- Only an issued invoice has a stored PDF; a draft has none to download (D-050) --}}
@if ($entry->issued_at !== null)
    @allowed(GetIssuedInvoicePdf::class)
        <a href="{{ route('admin.invoices.download', ['id' => $entry->getKey()]) }}" class="btn btn-sm btn-link">
            <i class="la la-file-pdf"></i> {{ __('invoices.actions.download_pdf') }}
        </a>
    @endallowed
@endif
