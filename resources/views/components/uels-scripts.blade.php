{{--
UELS JavaScript Integration Component
Provides translations and auto-initialization for UELS widgets
--}}
@props([
'autoLoad' => true
])

@once
@push('scripts')
@vite('resources/js/uels-manager.js')
<script>
    // Initialize UELS translations for JavaScript
window.UELSTranslations = {
    modal: {
        title: '{{ __('uels.modal.title') }}',
        loading: '{{ __('uels.modal.loading') }}',
        error: '{{ __('uels.modal.error') }}',
        close: '{{ __('uels.modal.close') }}',
        refresh: '{{ __('uels.modal.refresh') }}',
        retry: '{{ __('uels.modal.retry') }}',
        overview: '{{ __('uels.modal.overview') }}',
        current_period: '{{ __('uels.modal.current_period') }}',
        recent_activity: '{{ __('uels.modal.recent_activity') }}',
        current_usage: '{{ __('uels.modal.current_usage') }}',
        limit: '{{ __('uels.modal.limit_info') }}',
        usage_percentage: '{{ __('uels.modal.usage_percentage') }}',
        today: '{{ __('uels.modal.today') }}',
        this_week: '{{ __('uels.modal.this_week') }}',
        this_month: '{{ __('uels.modal.this_month') }}',
        total: '{{ __('uels.modal.total') }}',
        no_activity: '{{ __('uels.modal.no_activity') }}',
        entity_created: '{{ __('uels.modal.entity_created') }}'
    },
    entities: {
        clients: '{{ __('uels.entities.clients') }}',
        client: '{{ __('uels.entities.client') }}',
        suppliers: '{{ __('uels.entities.suppliers') }}',
        supplier: '{{ __('uels.entities.supplier') }}',
        products: '{{ __('uels.entities.products') }}',
        product: '{{ __('uels.entities.product') }}',
        invoices: '{{ __('uels.entities.invoices') }}',
        invoice: '{{ __('uels.entities.invoice') }}'
    },
    status: {
        success: '{{ __('uels.status.success') }}',
        warning: '{{ __('uels.status.warning') }}',
        danger: '{{ __('uels.status.danger') }}',
        no_limit: '{{ __('uels.status.no_limit') }}'
    },
    widget: {
        unlimited: '{{ __('uels.widget.unlimited') }}',
        title: '{{ __('uels.widget.title') }}',
        usage: '{{ __('uels.widget.usage') }}',
        loading: '{{ __('uels.widget.loading') }}',
        limit_exceeded: '{{ __('uels.widget.limit_exceeded') }}',
        approaching_limit: '{{ __('uels.widget.approaching_limit') }}',
        show_details: '{{ __('uels.widget.show_details') }}'
    }
};

@if($autoLoad)
// Auto-load widget data when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Wait for uelsManager to be available
    if (window.uelsManager && window.uelsManager.loadAllWidgets) {
        window.uelsManager.loadAllWidgets();
    } else {
        // Retry after a short delay if not ready
        setTimeout(function() {
            if (window.uelsManager && window.uelsManager.loadAllWidgets) {
                window.uelsManager.loadAllWidgets();
            }
        }, 100);
    }
});
@endif
</script>
@endpush
@endonce
