/**
 * Base validator class for form field validation
 * Provides core validation functionality that can be extended by specific validators
 */
export default class BaseValidator {
    constructor(options = {}) {
        this.options = {
            showAlerts: true,
            useCustomUI: false,
            validateOnBlur: true,
            validateOnSubmit: true,
            ...options
        };
        
        this.errors = new Map();
    }
    
    /**
     * Validates if a value is not empty
     * @param {string|number} value - The value to validate
     * @param {string} errorMessage - Error message to display if validation fails
     * @returns {boolean} - Whether the validation passed
     */
    validateRequired(value, errorMessage) {
        const valid = value !== null && value !== undefined && String(value).trim() !== '';
        
        if (!valid && errorMessage) {
            this.addError('required', errorMessage);
        }
        
        return valid;
    }
    
    /**
     * Validates if a value is numeric
     * @param {string|number} value - The value to validate
     * @param {string} errorMessage - Error message to display if validation fails
     * @returns {boolean} - Whether the validation passed
     */
    validateNumeric(value, errorMessage) {
        // Allow empty values - they should be caught by required validation if needed
        if (value === null || value === undefined || String(value).trim() === '') {
            return true;
        }
        
        // Convert to string and replace comma with period (for European number format)
        const normalizedValue = String(value).replace(',', '.');
        
        // Check if it's a valid number
        const valid = !isNaN(parseFloat(normalizedValue)) && isFinite(normalizedValue);
        
        if (!valid && errorMessage) {
            this.addError('numeric', errorMessage);
        }
        
        return valid;
    }
    
    /**
     * Validates if a value matches a regex pattern
     * @param {string} value - The value to validate
     * @param {RegExp} pattern - Regular expression pattern
     * @param {string} errorMessage - Error message to display if validation fails
     * @returns {boolean} - Whether the validation passed
     */
    validatePattern(value, pattern, errorMessage) {
        // Allow empty values - they should be caught by required validation if needed
        if (value === null || value === undefined || String(value).trim() === '') {
            return true;
        }
        
        const valid = pattern.test(String(value));
        
        if (!valid && errorMessage) {
            this.addError('pattern', errorMessage);
        }
        
        return valid;
    }
    
    /**
     * Add an error message
     * @param {string} key - Error key/type
     * @param {string} message - Error message
     */
    addError(key, message) {
        this.errors.set(key, message);
        
        if (this.options.showAlerts) {
            alert(message);
        }
    }
    
    /**
     * Check if there are validation errors
     * @returns {boolean} - True if there are errors
     */
    hasErrors() {
        return this.errors.size > 0;
    }
    
    /**
     * Clear all validation errors
     */
    clearErrors() {
        this.errors.clear();
    }
    
    /**
     * Get all validation errors
     * @returns {object} - Object with error messages
     */
    getErrors() {
        const errors = {};
        this.errors.forEach((message, key) => {
            errors[key] = message;
        });
        return errors;
    }
}
