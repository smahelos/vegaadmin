/**
 * Bank fields functionality
 * 
 * This script provides functionality for bank-related fields:
 * - Auto-fill bank name based on selected code
 * - Auto-fill SWIFT based on selected code
 * - Auto-select bank code based on entered SWIFT
 * - Auto-fill bank code and SWIFT based on entered bank name
 * - Format IBAN and SWIFT to uppercase without spaces
 */
class BankFieldsManager {
    constructor(bankOptions = {}) {
        // Store elements
        this.bankCodeSelect = document.getElementById('bank_code');
        this.bankNameInput = document.getElementById('bank_name');
        this.swiftInput = document.getElementById('swift');
        this.ibanInput = document.getElementById('iban');
        
        // Store bank data
        this.bankOptions = bankOptions;
        
        // Create reverse lookups for searching
        this.createReverseLookups();
        
        // Initialize functionality
        this.init();
    }
    
    /**
     * Create reverse lookups for searching by name and SWIFT
     */
    createReverseLookups() {
        // For looking up bank codes by name and SWIFT
        this.bankNameToCode = {};
        this.swiftToCode = {};
        
        // For looking up SWIFT by code
        this.codeToSwift = {};
        
        // Process all bank options
        for (const [code, bankData] of Object.entries(this.bankOptions)) {
            // Skip non-object entries (like the first item which is just a string)
            if (typeof bankData !== 'object' || bankData === null) continue;

            // Extract clean bank name (without code)
            const fullName = bankData.text || '';
            const name = fullName.replace(/\s+\(\d+\)$/, '').toLowerCase();
            const swift = bankData.swift || '';
            
            // Store mappings
            if (name) {
                this.bankNameToCode[name] = code;
            }
            
            if (swift) {
                this.swiftToCode[swift] = code;
                this.codeToSwift[code] = swift;
            }
        }
    }
    
    /**
     * Initialize all event listeners and functionality
     */
    init() {
        // Check if all required elements exist
        if (!this.bankCodeSelect) return;
        
        // 1. Update bank name and SWIFT when bank code changes
        this.bankCodeSelect.addEventListener('change', () => {
            this.updateBankNameFromCode();
            this.updateSwiftFromCode();
        });
        
        // 2. Format IBAN and SWIFT fields
        this.setupFormatting();
        
        // 3. Setup auto-fill logic for SWIFT input
        if (this.swiftInput) {
            this.swiftInput.addEventListener('input', () => {
                this.updateBankCodeFromSwift();
            });
        }
        
        // 4. Setup auto-fill logic for bank name input
        if (this.bankNameInput) {
            this.bankNameInput.addEventListener('input', () => {
                this.updateBankCodeFromName();
            });
        }
        
        // 5. Initialize values if bank code is already selected
        if (this.bankCodeSelect.value) {
            this.updateBankNameFromCode();
            this.updateSwiftFromCode();
        }
    }
    
    /**
     * Update bank name based on selected code
     */
    updateBankNameFromCode() {
        if (!this.bankNameInput) return;
        
        const selectedCode = this.bankCodeSelect.value;
        if (selectedCode && this.bankOptions[selectedCode] && this.bankOptions[selectedCode].text) {
            // Extract bank name from option value (remove code in parentheses)
            this.bankNameInput.value = this.bankOptions[selectedCode].text.replace(/\s+\(\d+\)$/, '');
        } else {
            this.bankNameInput.value = '';
        }
    }
    
    /**
     * Update SWIFT based on selected code
     */
    updateSwiftFromCode() {
        if (!this.swiftInput) return;
        
        const selectedCode = this.bankCodeSelect.value;
        if (selectedCode && this.bankOptions[selectedCode] && this.bankOptions[selectedCode].swift) {
            this.swiftInput.value = this.bankOptions[selectedCode].swift;
        } else if (selectedCode && this.codeToSwift[selectedCode]) {
            // Fallback to lookup table if swift isn't directly in the data
            this.swiftInput.value = this.codeToSwift[selectedCode];
        } else {
            this.swiftInput.value = '';
        }
    }
    
    /**
     * Update bank code based on entered SWIFT
     */
    updateBankCodeFromSwift() {
        const swift = this.swiftInput.value.replace(/\s+/g, '').toUpperCase();
        
        if (swift.length >= 3) {
            // First try direct match from bank data
            for (const [code, bankData] of Object.entries(this.bankOptions)) {
                if (typeof bankData === 'object' && bankData !== null && 
                    bankData.swift && bankData.swift.startsWith(swift)) {
                    this.bankCodeSelect.value = code;
                    this.updateBankNameFromCode();
                    return;
                }
            }
            
            // If no direct match, try lookup table
            for (const [swiftCode, bankCode] of Object.entries(this.swiftToCode)) {
                if (swiftCode.startsWith(swift)) {
                    this.bankCodeSelect.value = bankCode;
                    this.updateBankNameFromCode();
                    return;
                }
            }
        }
    }
    
    /**
     * Update bank code and SWIFT based on entered bank name
     */
    updateBankCodeFromName() {
        const name = this.bankNameInput.value.toLowerCase();
        
        if (name.length >= 2) {
            // First, try to match against bankOptions directly
            for (const [code, bankData] of Object.entries(this.bankOptions)) {
                if (typeof bankData !== 'object' || bankData === null) continue;
                
                const bankName = bankData.text ? 
                    bankData.text.replace(/\s+\(\d+\)$/, '').toLowerCase() : '';
                
                if (bankName && bankName.includes(name)) {
                    this.bankCodeSelect.value = code;
                    this.updateSwiftFromCode();
                    return;
                }
            }
            
            // If no direct match, try lookup table
            for (const [bankName, bankCode] of Object.entries(this.bankNameToCode)) {
                if (bankName.includes(name)) {
                    this.bankCodeSelect.value = bankCode;
                    this.updateSwiftFromCode();
                    return;
                }
            }
        }
    }
    
    /**
     * Setup formatting for IBAN and SWIFT inputs
     */
    setupFormatting() {
        const formatInputToUpperCase = (element) => {
            if (element) {
                element.addEventListener('input', function() {
                    this.value = this.value.replace(/\s+/g, '').toUpperCase();
                });
            }
        };
        
        formatInputToUpperCase(this.ibanInput);
        formatInputToUpperCase(this.swiftInput);
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Get bank options from the window object (will be set by the view)
    if (window.bankOptions) {
        new BankFieldsManager(window.bankOptions);
    }
});

// Export for potential usage in other files
if (typeof module !== 'undefined' && module.exports) {
    module.exports = BankFieldsManager;
}
