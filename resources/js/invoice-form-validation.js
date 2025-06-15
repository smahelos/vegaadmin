// Invoice form validation
// This module validates the invoice form fields before submission.
// It checks if the supplier, client, and payment amount fields are filled out correctly.
// It also ensures that the payment amount is numeric.
// If any validation fails, an alert is shown and the form submission is prevented.
export default class InvoiceFormValidation {
    constructor(messages = {}) {
        this.form = null;
        this.messages = Object.assign({
            supplierRequired: 'Supplier is required',
            clientRequired: 'Client is required',
            amountRequired: 'Amount is required',
            amountNumeric: 'Amount must be numeric'
        }, messages);
    }
    
    init() {
        this.form = document.querySelector('form');
        if (!this.form) return;
        
        this.form.addEventListener('submit', this.validateForm.bind(this));
    }
    
    validateForm(e) {
        // Přidám detailnější log pro debugování
        if (e && e.type) {
            console.log(`Validating form, event type: ${e.type}, target:`, e.target ? e.target.className : 'unknown');
        }
        
        // If validation is suppressed by a global flag, skip validation
        if (window.suppressValidationAlerts || window.skipNextPriceValidation) {
            console.log('Validation suppressed by flag');
            return true;
        }

        // Validate supplier
        const supplierSelect = document.getElementById('supplier_id');
        const supplierNameField = document.getElementById('name');
        
        let selectedSupplierId = null;
        let supplierNameValue = '';
        
        if (supplierSelect) selectedSupplierId = supplierSelect.value;
        if (supplierNameField) supplierNameValue = supplierNameField.value.trim();
        
        if (!this.validateRequired(selectedSupplierId || supplierNameValue, this.messages.supplierRequired, e)) {
            return false;
        }
        
        // Validate client
        const clientSelect = document.getElementById('client_id');
        const clientNameField = document.getElementById('client_name');
        
        let selectedClientId = null;
        let clientNameValue = '';
        
        if (clientSelect) selectedClientId = clientSelect.value;
        if (clientNameField) clientNameValue = clientNameField.value.trim();
        
        if (!this.validateRequired(selectedClientId || clientNameValue, this.messages.clientRequired, e)) {
            return false;
        }
        
        // Validate amount
        const paymentAmount = document.getElementById('payment_amount');

        // 1. If it is not a submit, skip validation
        // 2. If the event is on an invoice item, skip validation
        const isItemEvent = e && e.target && (
            e.target.classList.contains('item-price') ||
            e.target.classList.contains('item-quantity') ||
            e.target.classList.contains('item-name') ||
            e.target.classList.contains('item-tax') ||
            (e.target.closest && e.target.closest('.invoice-item'))
        );

        // Check if there is at least one item with a price (existing code)
        const hasItemWithPrice = document.querySelectorAll('.invoice-item .item-price').length > 0 && 
                                Array.from(document.querySelectorAll('.invoice-item .item-price'))
                                .some(input => input.value && input.value.trim() !== '');
        
        // Only validate amount on form submission, not on field events
        const shouldValidateAmount = paymentAmount && e && e.type === 'submit';
                            
        if (shouldValidateAmount) {
            console.log('Validating payment amount on submit, hasItemWithPrice:', hasItemWithPrice);
            
            // If there are items with price but payment_amount is empty, recalculate it
            if (hasItemWithPrice && (!paymentAmount.value || paymentAmount.value.trim() === '')) {
                if (window.invoiceItemManager) {
                    window.invoiceItemManager.updateTotalAmount();
                    console.log('Recalculated total amount before validation');
                }
            }
            
            // Check payment amount value after potential recalculation
            const paymentAmountValue = paymentAmount.value.trim();
            
            if (!this.validateRequired(paymentAmountValue, this.messages.amountRequired, e)) {
                return false;
            }
            
            if (isNaN(paymentAmountValue)) {
                e.preventDefault();
                alert(this.messages.amountNumeric);
                return false;
            }
        } else if (isItemEvent) {
            // For item events, don't show validation alerts
            console.log('Skipping payment amount validation for item event');
            return true;
        } else {
            console.log('Skipping payment amount validation, not a submit event');
        }
        
        return true;
    }
    
    validateRequired(value, message, e) {
        if (!value) {
            if (e) e.preventDefault();
            alert(message);
            return false;
        }
        return true;
    }
}
