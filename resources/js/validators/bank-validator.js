import BaseValidator from './base-validator.js';

/**
 * Bank validator 
 * Handles validation for IBAN, SWIFT/BIC codes, and other banking details
 */
export default class BankValidator extends BaseValidator {
    constructor(options = {}) {
        super(options);
        
        this.messages = {
            invalidIban: 'Neplatný IBAN',
            invalidSwift: 'Neplatný SWIFT/BIC kód',
            invalidAccountNumber: 'Neplatné číslo účtu',
            ...options.messages
        };
    }
    
    /**
     * Validates an IBAN (International Bank Account Number)
     * @param {string} iban - IBAN to validate
     * @returns {boolean} - Whether the IBAN is valid
     */
    validateIban(iban) {
        // Allow empty values - they should be caught by required validation if needed
        if (!iban || String(iban).trim() === '') {
            return true;
        }
        
        // Remove spaces and convert to uppercase
        const normalizedIban = String(iban).replace(/\s/g, '').toUpperCase();
        
        // Basic format check (length, starts with country code)
        if (normalizedIban.length < 5 || !/^[A-Z]{2}/.test(normalizedIban)) {
            this.addError('invalidIban', this.messages.invalidIban);
            return false;
        }
        
        // For Czech IBANs, perform additional check
        if (normalizedIban.startsWith('CZ')) {
            if (normalizedIban.length !== 24) {
                this.addError('invalidIban', this.messages.invalidIban);
                return false;
            }
        }
        
        // Move first 4 chars to the end
        let rearrangedIban = normalizedIban.substr(4) + normalizedIban.substr(0, 4);
        
        // Replace letters with numbers (A=10, B=11, etc.)
        let numericIban = '';
        for (let i = 0; i < rearrangedIban.length; i++) {
            let char = rearrangedIban.charAt(i);
            
            // If it's a letter, convert to number (A=10, B=11, etc.)
            if (/[A-Z]/.test(char)) {
                numericIban += (char.charCodeAt(0) - 55);
            } else {
                numericIban += char;
            }
        }
        
        // Standard mod-97 check
        let remainder = 0;
        for (let i = 0; i < numericIban.length; i += 7) {
            let chunk = remainder + '' + numericIban.substr(i, 7);
            remainder = parseInt(chunk, 10) % 97;
        }
        
        // If remainder is 1, the IBAN is valid
        const valid = remainder === 1;
        
        if (!valid) {
            this.addError('invalidIban', this.messages.invalidIban);
        }
        
        return valid;
    }
    
    /**
     * Validates a SWIFT/BIC code
     * @param {string} swift - SWIFT/BIC code to validate
     * @returns {boolean} - Whether the SWIFT/BIC is valid
     */
    validateSwift(swift) {
        // Allow empty values - they should be caught by required validation if needed
        if (!swift || String(swift).trim() === '') {
            return true;
        }
        
        // Remove spaces and convert to uppercase
        const normalizedSwift = String(swift).replace(/\s/g, '').toUpperCase();
        
        // SWIFT/BIC format: 8 or 11 characters
        // First 4: bank code (letters only)
        // Next 2: country code (letters only)
        // Next 2: location code (letters and digits)
        // Last 3: branch code (optional, letters and digits)
        const valid = /^[A-Z]{4}[A-Z]{2}[A-Z0-9]{2}([A-Z0-9]{3})?$/.test(normalizedSwift);
        
        if (!valid) {
            this.addError('invalidSwift', this.messages.invalidSwift);
        }
        
        return valid;
    }
    
    /**
     * Validates a Czech account number format
     * @param {string} accountNumber - Account number to validate
     * @returns {boolean} - Whether the account number is valid
     */
    validateCzechAccount(accountNumber) {
        // Allow empty values - they should be caught by required validation if needed
        if (!accountNumber || String(accountNumber).trim() === '') {
            return true;
        }
        
        // Remove all spaces and dashes
        const normalized = String(accountNumber).replace(/[\s-]/g, '');
        
        // Czech account number format: prefix-number/bank code
        // prefix is 0-6 digits, number is 2-10 digits
        const regex = /^(\d{0,6}-)?(\d{2,10})\/(\d{4})$/;
        
        const valid = regex.test(accountNumber);
        
        if (!valid) {
            this.addError('invalidAccountNumber', this.messages.invalidAccountNumber);
        }
        
        return valid;
    }
    
    /**
     * Format IBAN with proper spacing for display
     * @param {HTMLElement} inputElement - The IBAN input field
     */
    formatIbanField(inputElement) {
        inputElement.addEventListener('blur', function() {
            // Remove all spaces and convert to uppercase
            const rawValue = this.value.replace(/\s/g, '').toUpperCase();
            
            if (rawValue === '') {
                return;
            }
            
            // Format with a space every 4 characters
            this.value = rawValue.replace(/(.{4})/g, '$1 ').trim();
        });
    }
    
    /**
     * Format SWIFT/BIC with proper spacing for display
     * @param {HTMLElement} inputElement - The SWIFT/BIC input field
     */
    formatSwiftField(inputElement) {
        inputElement.addEventListener('blur', function() {
            // Remove all spaces and convert to uppercase
            const rawValue = this.value.replace(/\s/g, '').toUpperCase();
            
            if (rawValue === '') {
                return;
            }
            
            this.value = rawValue;
        });
    }
    
    /**
     * Attach validation to an IBAN field
     * @param {HTMLElement} inputElement - The IBAN input element
     */
    attachToIbanField(inputElement) {
        // Format the field on blur
        this.formatIbanField(inputElement);
        
        // Validate on blur if option is enabled
        if (this.options.validateOnBlur) {
            inputElement.addEventListener('blur', () => {
                this.validateIban(inputElement.value);
            });
        }
    }
    
    /**
     * Attach validation to a SWIFT/BIC field
     * @param {HTMLElement} inputElement - The SWIFT/BIC input element
     */
    attachToSwiftField(inputElement) {
        // Format the field on blur
        this.formatSwiftField(inputElement);
        
        // Validate on blur if option is enabled
        if (this.options.validateOnBlur) {
            inputElement.addEventListener('blur', () => {
                this.validateSwift(inputElement.value);
            });
        }
    }
}
