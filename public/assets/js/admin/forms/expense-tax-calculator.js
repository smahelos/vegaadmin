/**
 * Tax calculator for Expense CRUD
 * Calculates tax_amount based on selected tax_rate and amount
 */
document.addEventListener('DOMContentLoaded', function() {
    // Get references to the form elements
    const amountField = document.querySelector('input[name="amount"]');
    const taxRateSelect = document.querySelector('select[name="tax_rate"]');
    const taxIncludedField = document.querySelector('.tax_included_parent input[type="checkbox"]');
    const taxAmountField = document.querySelector('input[name="tax_amount"]');
    
    // Debug - log if fields were found
    console.log('Found form fields:', {
        amount: !!amountField,
        taxRate: !!taxRateSelect, 
        taxIncluded: !!taxIncludedField,
        taxAmount: !!taxAmountField
    });
    
    // Function to get tax rate percentage from select
    function getTaxRatePercentage() {
        if (taxRateSelect && taxRateSelect.value) {
            const rate = parseFloat(taxRateSelect.value);
            console.log('Tax rate from select value:', rate);
            return rate;
        }
        
        // If no value selected or field doesn't exist, return 0
        console.warn('Could not determine tax rate, using 0%');
        return 0;
    }
    
    // Helper function to determine if tax is included
    function isTaxIncluded() {
        if (!taxIncludedField) return true;
        
        if (taxIncludedField.type === 'checkbox') {
            return taxIncludedField.checked;
        } else {
            return taxIncludedField.value === '1' || taxIncludedField.value === 'true';
        }
    }
    
    // Function to calculate tax amount
    function calculateTaxAmount() {
        const amount = parseFloat(amountField.value) || 0;
        const taxRate = getTaxRatePercentage() / 100;
        
        // Get tax_included state using helper function
        const taxIncluded = isTaxIncluded();
        
        let taxAmount = 0;
        
        if (taxIncluded) {
            // If tax is included in the amount, extract it
            // Formula: taxAmount = amount - (amount / (1 + taxRate))
            taxAmount = amount - (amount / (1 + taxRate));
        } else {
            // If tax is not included, multiply by tax rate
            taxAmount = amount * taxRate;
        }
        
        // Update tax amount field with 2 decimal places
        if (taxAmountField) {
            taxAmountField.value = taxAmount.toFixed(2);
            
            // Trigger change event to notify any listeners
            const event = new Event('change', { bubbles: true });
            taxAmountField.dispatchEvent(event);
            
            console.log(`Tax calculated: ${taxAmount.toFixed(2)} (Tax rate: ${(taxRate * 100).toFixed(2)}%, Tax included: ${taxIncluded})`);
        }
    }
    
    // Function to determine tax rate from amount and tax_amount
    function determineTaxRate() {
        const amount = parseFloat(amountField.value) || 0;
        const taxAmount = parseFloat(taxAmountField.value) || 0;
        
        // Log input values for debugging
        console.log('Determining tax rate from:', {
            amount: amount,
            taxAmount: taxAmount,
            taxIncluded: isTaxIncluded()
        });
        
        if (amount <= 0) {
            console.log('Cannot determine tax rate: amount is zero or invalid');
            return;
        }
        
        // Get tax_included state using helper function
        const taxIncluded = isTaxIncluded();
        
        let taxRate = 0;
        
        if (taxIncluded) {
            // If tax is included, calculate the rate
            // taxAmount = amount - (amount / (1 + taxRate))
            // Solving for taxRate: taxRate = (amount / (amount - taxAmount)) - 1
            if (amount > taxAmount) {
                taxRate = (amount / (amount - taxAmount)) - 1;
            }
        } else {
            // If tax is not included, calculate rate
            // taxAmount = amount * taxRate
            // Solving for taxRate: taxRate = taxAmount / amount
            taxRate = taxAmount / amount;
        }
        
        // Convert to percentage and round to 2 decimal places
        const taxRatePercentage = (taxRate * 100).toFixed(2);
        console.log(`Determined tax rate: ${taxRatePercentage}% (Tax included: ${taxIncluded})`);
        
        // Find and select the matching option in the select
        if (taxRateSelect) {
            let foundMatch = false;
            
            for (let i = 0; i < taxRateSelect.options.length; i++) {
                const option = taxRateSelect.options[i];
                const optionValue = parseFloat(option.value);
                
                // Check if the option value approximately matches our calculated rate
                // Allow small rounding differences (0.01%)
                if (!isNaN(optionValue) && Math.abs(optionValue - taxRatePercentage) < 0.01) {
                    taxRateSelect.selectedIndex = i;
                    foundMatch = true;
                    console.log(`Selected matching tax rate option: ${option.text}`);
                    break;
                }
            }
            
            if (!foundMatch) {
                console.warn(`Could not find a matching tax rate option for ${taxRatePercentage}%`);
            }
            
            // Trigger change event to update any dependent components
            const event = new Event('change', { bubbles: true });
            taxRateSelect.dispatchEvent(event);
        }
    }
    
    // Set up event listeners for all relevant fields
    if (amountField) {
        amountField.addEventListener('input', calculateTaxAmount);
        amountField.addEventListener('change', calculateTaxAmount);
    }
    
    // Basic change event for the taxRateSelect
    if (taxRateSelect) {
        taxRateSelect.addEventListener('change', calculateTaxAmount);
    }
    
    // Handle tax_included field whether it's a checkbox or hidden input
    if (taxIncludedField) {
        if (taxIncludedField.type === 'checkbox') {
            taxIncludedField.addEventListener('change', calculateTaxAmount);
        } else {
            // For hidden fields, create a MutationObserver to watch for value changes
            const observer = new MutationObserver(calculateTaxAmount);
            observer.observe(taxIncludedField, { attributes: true });
        }
    }
    
    // Initialize function with better validation
    function initializeCalculator() {
        console.log('Initializing tax calculator with:', {
            amount: amountField?.value,
            taxAmount: taxAmountField?.value,
            taxIncluded: isTaxIncluded()
        });
        
        // If we have both amount and tax_amount, determine the tax rate
        if (amountField && amountField.value && 
            taxAmountField && taxAmountField.value && 
            parseFloat(amountField.value) > 0 && 
            parseFloat(taxAmountField.value) >= 0) {
            
            console.log('Determining tax rate from existing values');
            determineTaxRate();
        } else if (taxRateSelect && taxRateSelect.value) {
            // Otherwise, use the selected tax rate to calculate the tax amount
            console.log('Calculating tax amount from selected rate');
            calculateTaxAmount();
        }
    }
    
    // Initialize with slight delay to ensure all fields are properly loaded
    setTimeout(initializeCalculator, 100);
});
