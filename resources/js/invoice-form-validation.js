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
        if (paymentAmount) {
            const paymentAmountValue = paymentAmount.value.trim();
            
            if (!this.validateRequired(paymentAmountValue, this.messages.amountRequired, e)) {
                return false;
            }
            
            if (isNaN(paymentAmountValue)) {
                e.preventDefault();
                alert(this.messages.amountNumeric);
                return false;
            }
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
