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
window.UELSTranslations = @js([
    'modal' => __('entity_limits.modal'),
    'entities' => [
        'business' => __('entity_limits.entity_types.business.plural'),
        'contact' => __('entity_limits.entity_types.contact.plural'),
        'product' => __('entity_limits.entity_types.product.plural'),
        'invoice' => __('entity_limits.entity_types.invoice.plural'),
    ],
    'status' => [
        'success' => __('entity_limits.statuses.success'),
        'warning' => __('entity_limits.statuses.warning'),
        'danger' => __('entity_limits.statuses.danger'),
        'no_limit' => __('entity_limits.statuses.not_allowed'),
    ],
    'widget' => __('entity_limits.widget'),
]);

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
