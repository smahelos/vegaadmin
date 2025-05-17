import BaseValidator from './base-validator.js';

/**
 * Tax validator
 * Handles validation for tax identification numbers like VAT ID (DIČ) and business ID (IČ)
 */
export default class TaxValidator extends BaseValidator {
    constructor(options = {}) {
        super(options);
        
        this.messages = {
            invalidVatId: 'Neplatné DIČ',
            invalidBusinessId: 'Neplatné IČ',
            ...options.messages
        };
        
        // Map of country codes to their VAT ID patterns
        this.vatIdPatterns = {
            'CZ': /^CZ[0-9]{8,10}$/,
            'SK': /^SK[0-9]{10}$/,
            'DE': /^DE[0-9]{9}$/,
            'AT': /^ATU[0-9]{8}$/,
            'PL': /^PL[0-9]{10}$/,
            'GB': /^GB([0-9]{9}|[0-9]{12}|(HA|GD)[0-9]{3})$/,
            'EU': /^[A-Z]{2}[0-9A-Z]{2,12}$/ // Generic EU format
        };
    }
    
    /**
     * Validates a VAT ID (DIČ)
     * @param {string} vatId - VAT ID to validate
     * @param {string} countryCode - Country code (e.g., 'CZ', 'SK')
     * @returns {boolean} - Whether the VAT ID is valid
     */
    validateVatId(vatId, countryCode = 'CZ') {
        // Allow empty values - they should be caught by required validation if needed
        if (!vatId || String(vatId).trim() === '') {
            return true;
        }
        
        // Remove spaces and convert to uppercase
        const normalizedVatId = String(vatId).replace(/\s/g, '').toUpperCase();
        
        // If country code is not specified in the VAT ID, add it
        let vatIdWithCountry = normalizedVatId;
        if (!normalizedVatId.startsWith(countryCode)) {
            vatIdWithCountry = countryCode + normalizedVatId;
        }
        
        // Use the specific country pattern or fall back to EU generic pattern
        const pattern = this.vatIdPatterns[countryCode] || this.vatIdPatterns.EU;
        
        const isFormatValid = pattern.test(vatIdWithCountry);
        
        // For Czech VAT IDs, perform additional validation
        if (isFormatValid && countryCode === 'CZ') {
            // Extract the numeric part (remove CZ)
            const numericPart = vatIdWithCountry.substring(2);
            
            // Validate based on length
            if (numericPart.length === 8) {
                // Business ID validation (IČ/IČO)
                return this.validateCzechBusinessId(numericPart);
            } else if (numericPart.length === 9 || numericPart.length === 10) {
                // Person tax ID validation
                return true; // Simplified - would need specific algorithm for full validation
            }
        }
        
        const valid = isFormatValid;
        
        if (!valid) {
            this.addError('invalidVatId', this.messages.invalidVatId);
        }
        
        return valid;
    }
    
    /**
     * Validates a Czech business ID (IČ/IČO) using modulo 11 algorithm
     * @param {string} businessId - Business ID to validate
     * @returns {boolean} - Whether the business ID is valid
     */
    validateCzechBusinessId(businessId) {
        // Allow empty values - they should be caught by required validation if needed
        if (!businessId || String(businessId).trim() === '') {
            return true;
        }
        
        // Remove spaces
        const normalizedId = String(businessId).replace(/\s/g, '');
        
        // Check if it's 8 digits
        if (!/^\d{8}$/.test(normalizedId)) {
            this.addError('invalidBusinessId', this.messages.invalidBusinessId);
            return false;
        }
        
        // Apply modulo 11 check algorithm
        let sum = 0;
        const weights = [8, 7, 6, 5, 4, 3, 2];
        
        for (let i = 0; i < 7; i++) {
            sum += parseInt(normalizedId.charAt(i)) * weights[i];
        }
        
        const remainder = sum % 11;
        let checkDigit;
        
        if (remainder === 0) {
            checkDigit = 1;
        } else if (remainder === 1) {
            checkDigit = 0;
        } else {
            checkDigit = 11 - remainder;
        }
        
        const valid = parseInt(normalizedId.charAt(7)) === checkDigit;
        
        if (!valid) {
            this.addError('invalidBusinessId', this.messages.invalidBusinessId);
        }
        
        return valid;
    }
    
    /**
     * Format VAT ID field (DIČ)
     * @param {HTMLElement} inputElement - The VAT ID input field
     * @param {string} countryCode - Default country code to add if missing
     */
    formatVatIdField(inputElement, countryCode = 'CZ') {
        inputElement.addEventListener('blur', function() {
            // Remove spaces and convert to uppercase
            let value = this.value.replace(/\s/g, '').toUpperCase();
            
            if (value === '') {
                return;
            }
            
            // Add country code if missing and value is not empty
            if (!value.match(/^[A-Z]{2}/) && value !== '') {
                value = countryCode + value;
            }
            
            this.value = value;
        });
    }
    
    /**
     * Format business ID field (IČ)
     * @param {HTMLElement} inputElement - The business ID input field
     */
    formatBusinessIdField(inputElement) {
        inputElement.addEventListener('blur', function() {
            // Remove spaces
            const value = this.value.replace(/\s/g, '');
            
            if (value === '') {
                return;
            }
            
            // Format with proper spacing (e.g. 12345678 -> 12 345 678)
            this.value = value.replace(/(\d{2})(?=\d)/g, '$1 ').trim();
        });
    }
    
    /**
     * Attach validation to a VAT ID (DIČ) field
     * @param {HTMLElement} inputElement - The VAT ID input element
     * @param {string} countryCode - Country code for validation
     */
    attachToVatIdField(inputElement, countryCode = 'CZ') {
        // Format the field on blur
        this.formatVatIdField(inputElement, countryCode);
        
        // Validate on blur if option is enabled
        if (this.options.validateOnBlur) {
            inputElement.addEventListener('blur', () => {
                this.validateVatId(inputElement.value, countryCode);
            });
        }
    }
    
    /**
     * Attach validation to a business ID (IČ) field
     * @param {HTMLElement} inputElement - The business ID input element
     */
    attachToBusinessIdField(inputElement) {
        // Format the field on blur
        this.formatBusinessIdField(inputElement);
        
        // Validate on blur if option is enabled
        if (this.options.validateOnBlur) {
            inputElement.addEventListener('blur', () => {
                this.validateCzechBusinessId(inputElement.value);
            });
        }
    }
}
