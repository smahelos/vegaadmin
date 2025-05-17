import BaseValidator from './base-validator.js';

/**
 * Financial validator
 * Handles validation for financial amounts, currencies, and related fields
 */
export default class FinancialValidator extends BaseValidator {
    constructor(options = {}) {
        super(options);
        
        this.messages = {
            required: 'Částka je povinná',
            numeric: 'Částka musí být číslo',
            positive: 'Částka musí být kladné číslo',
            maxDecimals: 'Částka může mít maximálně {max} desetinná místa',
            validCurrency: 'Měna musí být platný ISO kód měny',
            ...options.messages
        };
    }
    
    /**
     * Validates if a financial amount is valid
     * @param {string|number} value - The amount to validate
     * @param {object} options - Validation options
     * @returns {boolean} - Whether the validation passed
     */
    validateAmount(value, options = {}) {
        const opts = {
            required: true,
            allowNegative: false,
            maxDecimals: 2,
            ...options
        };
        
        this.clearErrors();
        
        // Check if required
        if (opts.required && !this.validateRequired(value, this.messages.required)) {
            return false;
        }
        
        // Allow empty values if not required
        if (value === null || value === undefined || String(value).trim() === '') {
            return true;
        }
        
        // Convert to string and normalize format (replace comma with period)
        const normalizedValue = String(value).replace(',', '.');
        
        // Check if it's a valid number
        if (!this.validateNumeric(normalizedValue, this.messages.numeric)) {
            return false;
        }
        
        // Convert to float for further checks
        const floatValue = parseFloat(normalizedValue);
        
        // Check if it's positive when required
        if (!opts.allowNegative && floatValue < 0) {
            this.addError('positive', this.messages.positive);
            return false;
        }
        
        // Check decimal places
        if (opts.maxDecimals !== null) {
            const decimalStr = normalizedValue.toString().split('.')[1] || '';
            if (decimalStr.length > opts.maxDecimals) {
                this.addError('maxDecimals', this.messages.maxDecimals.replace('{max}', opts.maxDecimals));
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Validates if a currency code is valid (ISO 4217)
     * @param {string} value - The currency code to validate
     * @returns {boolean} - Whether the validation passed
     */
    validateCurrency(value) {
        const isoCurrencyCodes = ['AED', 'AFN', 'ALL', 'AMD', 'ANG', 'AOA', 'ARS', 'AUD', 'AWG', 'AZN', 'BAM', 'BBD', 'BDT', 'BGN', 'BHD', 'BIF', 'BMD', 'BND', 'BOB', 'BRL', 'BSD', 'BTN', 'BWP', 'BYN', 'BZD', 'CAD', 'CDF', 'CHF', 'CLP', 'CNY', 'COP', 'CRC', 'CUC', 'CUP', 'CVE', 'CZK', 'DJF', 'DKK', 'DOP', 'DZD', 'EGP', 'ERN', 'ETB', 'EUR', 'FJD', 'FKP', 'GBP', 'GEL', 'GGP', 'GHS', 'GIP', 'GMD', 'GNF', 'GTQ', 'GYD', 'HKD', 'HNL', 'HRK', 'HTG', 'HUF', 'IDR', 'ILS', 'IMP', 'INR', 'IQD', 'IRR', 'ISK', 'JEP', 'JMD', 'JOD', 'JPY', 'KES', 'KGS', 'KHR', 'KMF', 'KPW', 'KRW', 'KWD', 'KYD', 'KZT', 'LAK', 'LBP', 'LKR', 'LRD', 'LSL', 'LYD', 'MAD', 'MDL', 'MGA', 'MKD', 'MMK', 'MNT', 'MOP', 'MRU', 'MUR', 'MVR', 'MWK', 'MXN', 'MYR', 'MZN', 'NAD', 'NGN', 'NIO', 'NOK', 'NPR', 'NZD', 'OMR', 'PAB', 'PEN', 'PGK', 'PHP', 'PKR', 'PLN', 'PYG', 'QAR', 'RON', 'RSD', 'RUB', 'RWF', 'SAR', 'SBD', 'SCR', 'SDG', 'SEK', 'SGD', 'SHP', 'SLL', 'SOS', 'SPL', 'SRD', 'STN', 'SVC', 'SYP', 'SZL', 'THB', 'TJS', 'TMT', 'TND', 'TOP', 'TRY', 'TTD', 'TVD', 'TWD', 'TZS', 'UAH', 'UGX', 'USD', 'UYU', 'UZS', 'VEF', 'VND', 'VUV', 'WST', 'XAF', 'XCD', 'XDR', 'XOF', 'XPF', 'YER', 'ZAR', 'ZMW', 'ZWD'];

        // Allow empty values - they should be caught by required validation if needed
        if (value === null || value === undefined || String(value).trim() === '') {
            return true;
        }

        const valid = isoCurrencyCodes.includes(String(value).toUpperCase());
        
        if (!valid) {
            this.addError('validCurrency', this.messages.validCurrency);
        }
        
        return valid;
    }
    
    /**
     * Apply financial format to an input field (on blur)
     * @param {HTMLElement} inputElement - The input element to format
     * @param {object} options - Formatting options
     */
    formatAmountField(inputElement, options = {}) {
        const opts = {
            decimals: 2,
            decimalSeparator: ',',
            thousandSeparator: ' ',
            ...options
        };
        
        inputElement.addEventListener('blur', function() {
            const rawValue = this.value.replace(/\s/g, '').replace(',', '.');
            
            if (rawValue === '' || isNaN(parseFloat(rawValue))) {
                return;
            }
            
            const number = parseFloat(rawValue);
            
            // Format the number
            const parts = number.toFixed(opts.decimals).split('.');
            parts[0] = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, opts.thousandSeparator);
            
            // Join with appropriate decimal separator
            this.value = parts.join(opts.decimalSeparator);
        });
    }
    
    /**
     * Attach validation to an amount input field
     * @param {HTMLElement} inputElement - The input element to validate
     * @param {object} options - Validation options
     */
    attachToField(inputElement, options = {}) {
        // Apply formatting on blur
        this.formatAmountField(inputElement, options);
        
        // Validate on blur if option is enabled
        if (this.options.validateOnBlur) {
            inputElement.addEventListener('blur', () => {
                this.validateAmount(inputElement.value, options);
            });
        }
    }
}
