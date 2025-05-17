import InvoiceFormValidation from './invoice-form-validation.js';
import FinancialValidator from './validators/financial-validator.js';
import BankValidator from './validators/bank-validator.js';
import TaxValidator from './validators/tax-validator.js';

/**
 * Extended invoice form validation
 * Adds comprehensive validation for financial amounts, IBAN, VAT ID (DIČ), etc.
 */
export default class InvoiceFormValidationExtended extends InvoiceFormValidation {
    constructor(messages = {}) {
        super(messages);
        
        // Initialize specialized validators
        this.financialValidator = new FinancialValidator({
            messages: {
                required: messages.amountRequired || 'Částka je povinná',
                numeric: messages.amountNumeric || 'Částka musí být číslo',
                positive: messages.amountPositive || 'Částka musí být kladné číslo'
            }
        });
        
        this.bankValidator = new BankValidator({
            messages: {
                invalidIban: messages.invalidIban || 'Neplatný IBAN',
                invalidSwift: messages.invalidSwift || 'Neplatný SWIFT/BIC kód'
            }
        });
        
        this.taxValidator = new TaxValidator({
            messages: {
                invalidVatId: messages.invalidVatId || 'Neplatné DIČ',
                invalidBusinessId: messages.invalidBusinessId || 'Neplatné IČ'
            }
        });
    }
    
    init() {
        super.init();
        
        // Initialize field-specific validations
        this.initializeFieldValidations();
    }
    
    initializeFieldValidations() {
        // Financial amount validations
        const amountFields = document.querySelectorAll('input[name="payment_amount"], input.item-price, input.item-price-complete');
        amountFields.forEach(field => {
            this.financialValidator.attachToField(field, {
                allowNegative: false,
                maxDecimals: 2,
                validateOnBlur: true
            });
        });
        
        // IBAN validation
        const ibanField = document.getElementById('iban');
        if (ibanField) {
            this.bankValidator.attachToIbanField(ibanField);
        }
        
        // SWIFT validation
        const swiftField = document.getElementById('swift');
        if (swiftField) {
            this.bankValidator.attachToSwiftField(swiftField);
        }
        
        // VAT ID (DIČ) validation
        const vatIdField = document.getElementById('vat_id');
        if (vatIdField) {
            this.taxValidator.attachToVatIdField(vatIdField);
        }
        
        // Business ID (IČ) validation
        const businessIdField = document.getElementById('business_id');
        if (businessIdField) {
            this.taxValidator.attachToBusinessIdField(businessIdField);
        }
    }
    
    validateForm(e) {
        let isValid = super.validateForm(e);
        
        if (!isValid) {
            return false;
        }
        
        // Advanced validations specific to invoice forms
        const paymentAmount = document.getElementById('payment_amount');
        if (paymentAmount) {
            const isAmountValid = this.financialValidator.validateAmount(paymentAmount.value, {
                required: true,
                allowNegative: false,
                maxDecimals: 2
            });
            
            if (!isAmountValid) {
                e.preventDefault();
                return false;
            }
        }
        
        // IBAN validation if present
        const ibanField = document.getElementById('iban');
        if (ibanField && ibanField.value.trim()) {
            const isIbanValid = this.bankValidator.validateIban(ibanField.value);
            
            if (!isIbanValid) {
                e.preventDefault();
                return false;
            }
        }
        
        // SWIFT validation if present
        const swiftField = document.getElementById('swift');
        if (swiftField && swiftField.value.trim()) {
            const isSwiftValid = this.bankValidator.validateSwift(swiftField.value);
            
            if (!isSwiftValid) {
                e.preventDefault();
                return false;
            }
        }
        
        // VAT ID validation if present
        const vatIdField = document.getElementById('vat_id');
        if (vatIdField && vatIdField.value.trim()) {
            const countryCode = document.getElementById('country_code')?.value || 'CZ';
            const isVatIdValid = this.taxValidator.validateVatId(vatIdField.value, countryCode);
            
            if (!isVatIdValid) {
                e.preventDefault();
                return false;
            }
        }
        
        // Business ID validation if present
        const businessIdField = document.getElementById('business_id');
        if (businessIdField && businessIdField.value.trim()) {
            const isBusinessIdValid = this.taxValidator.validateCzechBusinessId(businessIdField.value);
            
            if (!isBusinessIdValid) {
                e.preventDefault();
                return false;
            }
        }
        
        // Validate invoice items if present
        const items = document.querySelectorAll('.invoice-item');
        if (items.length > 0) {
            let itemsValid = true;
            
            items.forEach(item => {
                const nameField = item.querySelector('.item-name');
                const priceField = item.querySelector('.item-price');
                
                // Validate item name
                if (nameField && !nameField.value.trim()) {
                    itemsValid = false;
                    alert(this.messages.itemNameRequired || 'Název položky je povinný');
                    return;
                }
                
                // Validate item price
                if (priceField) {
                    const isPriceValid = this.financialValidator.validateAmount(priceField.value, {
                        required: false,
                        allowNegative: false,
                        maxDecimals: 2
                    });
                    
                    if (!isPriceValid) {
                        itemsValid = false;
                        return;
                    }
                }
            });
            
            if (!itemsValid) {
                e.preventDefault();
                return false;
            }
        }
        
        return true;
    }
}
