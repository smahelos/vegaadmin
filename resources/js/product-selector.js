/**
 * Product selector functionality
 * Handles communication between invoice item manager and Livewire product selector component
 */
document.addEventListener('DOMContentLoaded', function() {
    // Add global event listener for invoice-products-loaded
    window.addEventListener('invoice-products-loaded', handleProductsLoaded);
    
    function handleProductsLoaded(e) {
        if (!e.detail || !e.detail.selectedProductIds) return;
        
        // Get component ID from hidden element
        const componentIdHolder = document.getElementById('component-id-holder');
        if (!componentIdHolder) return;
        
        const componentId = componentIdHolder.dataset.componentId;
        if (!componentId) return;
        
        // Use standard Livewire API
        if (window.Livewire) {
            // Try multiple approaches to work with different Livewire versions
            if (typeof window.Livewire.find === 'function') {
                const component = window.Livewire.find(componentId);
                if (component && typeof component.call === 'function') {
                    component.call('setSelectedProductIds', e.detail.selectedProductIds);
                }
            } else if (typeof window.Livewire.dispatch === 'function') {
                // For newer Livewire versions
                window.Livewire.dispatch('setSelectedProductIds', { 
                    ids: e.detail.selectedProductIds, 
                    componentId: componentId 
                });
            }
        }
    }
});
