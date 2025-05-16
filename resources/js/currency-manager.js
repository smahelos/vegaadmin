/**
 * Currency Manager for invoice forms
 * Handles synchronization of currency fields and conversion of amounts
 */
export default class CurrencyManager {
    constructor() {
        // Main form elements
        this.paymentCurrencySelect = document.getElementById('payment_currency');
        this.paymentAmountInput = document.getElementById('payment_amount');
        this.itemsContainer = document.getElementById('invoice-items-list');
        
        // Exchange rates cache
        this.exchangeRates = {};
        
        // Initialize if required elements are present
        if (this.paymentCurrencySelect && this.itemsContainer) {
            this.init();
        }
    }
    
    /**
     * Initialize the currency manager
     */
    init() {
        console.log('CurrencyManager initialized');
        
        // Add event listener for payment currency change
        this.paymentCurrencySelect.addEventListener('change', this.handlePaymentCurrencyChange.bind(this));
        
        // Add event delegation for item currency changes
        this.itemsContainer.addEventListener('change', this.handleItemCurrencyChange.bind(this));
        
        // Initialize with current payment currency
        this.currentCurrency = this.paymentCurrencySelect.value;
    }
    
    /**
     * Handle change of payment currency
     * @param {Event} event 
     */
    async handlePaymentCurrencyChange(event) {
        const newCurrency = event.target.value;
        const oldCurrency = this.currentCurrency;
        
        // Skip if currency didn't change
        if (newCurrency === oldCurrency) {
            return;
        }
        
        // Show confirmation dialog
        if (!await this.confirmCurrencyChange(oldCurrency, newCurrency)) {
            // Reset to old value if user canceled
            event.target.value = oldCurrency;
            return;
        }
        
        // Update all item currencies
        this.updateAllItemCurrencies(newCurrency);
        
        // Convert payment amount and prices
        await this.convertAllPrices(oldCurrency, newCurrency);
        
        // Update current currency
        this.currentCurrency = newCurrency;
    }
    
    /**
     * Handle change of an item currency
     * @param {Event} event 
     */
    async handleItemCurrencyChange(event) {
        // Only handle item currency changes
        if (!event.target.classList.contains('item-currency')) {
            return;
        }
        
        const itemRow = event.target.closest('.invoice-item');
        if (!itemRow) {
            console.error('Could not find parent invoice-item for currency change');
            return;
        }
        
        const newCurrency = event.target.value;
        const oldCurrency = itemRow.dataset.currentCurrency || this.currentCurrency;
        
        // Skip if currency didn't change
        if (newCurrency === oldCurrency) {
            return;
        }
        
        console.log(`Item currency change detected: ${oldCurrency} -> ${newCurrency} for row`, itemRow);
        
        // Show confirmation dialog
        if (!await this.confirmCurrencyChange(oldCurrency, newCurrency)) {
            // Reset to old value if user canceled
            event.target.value = oldCurrency;
            return;
        }
        
        // Set payment currency
        this.paymentCurrencySelect.value = newCurrency;
        
        // Store previous currency in the row for accurate conversion
        itemRow.dataset.previousCurrency = oldCurrency;
        itemRow.dataset.newCurrency = newCurrency;
        itemRow.dataset.conversionRequested = 'true';
        
        // Update all item currencies
        this.updateAllItemCurrencies(newCurrency);
        
        // Convert payment amount and prices
        await this.convertAllPrices(oldCurrency, newCurrency);
        
        // Update current currency
        this.currentCurrency = newCurrency;
    }
    
    /**
     * Show confirmation dialog for currency change
     * @param {string} oldCurrency 
     * @param {string} newCurrency 
     * @returns {Promise<boolean>}
     */
    async confirmCurrencyChange(oldCurrency, newCurrency) {
        return new Promise((resolve) => {
            // Use translation
            let message = '';
            if (window.translations && window.translations.currency_change_confirmation) {
                message = window.translations.currency_change_confirmation
                    .replace(':from', oldCurrency)
                    .replace(':to', newCurrency);
            } else {
                // Fallback if translations not available
                message = `${oldCurrency} to ${newCurrency} currency change will affect all invoice items. Do you want to continue?`;
            }
            
            if (confirm(message)) {
                resolve(true);
            } else {
                resolve(false);
            }
        });
    }
    
    /**
     * Update all item currencies to the new currency
     * @param {string} newCurrency 
     */
    updateAllItemCurrencies(newCurrency) {
        const itemCurrencySelects = this.itemsContainer.querySelectorAll('.item-currency');
        
        itemCurrencySelects.forEach(select => {
            select.value = newCurrency;
        });
    }
    
    /**
     * Get exchange rate from API
     * @param {string} fromCurrency 
     * @param {string} toCurrency 
     * @returns {Promise<number>}
     */
    async getExchangeRate(fromCurrency, toCurrency) {
        // Return 1 if same currency
        if (fromCurrency === toCurrency) {
            return 1;
        }
        
        // Check cache first
        const rateKey = `${fromCurrency}_${toCurrency}`;
        if (this.exchangeRates[rateKey]) {
            return this.exchangeRates[rateKey];
        }
        
        try {
            const response = await fetchWithSession(`/api/currencies/exchange-rate?from=${fromCurrency}&to=${toCurrency}`);
            
            // Cache the rate
            this.exchangeRates[rateKey] = response.rate;
            
            return response.rate;
        } catch (error) {
            console.error('Error fetching exchange rate:', error);
            // Fallback rate (1:1) in case of errors
            return 1;
        }
    }
    
    /**
     * Convert an amount from one currency to another
     * @param {number} amount 
     * @param {string} fromCurrency 
     * @param {string} toCurrency 
     * @returns {Promise<number>}
     */
    async convertAmount(amount, fromCurrency, toCurrency) {
        const rate = await this.getExchangeRate(fromCurrency, toCurrency);
        return parseFloat((amount * rate).toFixed(2));
    }
    
    /**
     * Convert all prices on the invoice
     * @param {string} fromCurrency 
     * @param {string} toCurrency 
     */
    async convertAllPrices(fromCurrency, toCurrency) {
        // Show loading state
        this.setLoadingState(true);
        
        try {
            // Convert payment amount if it's not readonly
            if (this.paymentAmountInput && !this.paymentAmountInput.readOnly) {
                const originalAmount = parseFloat(this.paymentAmountInput.value) || 0;
                const convertedAmount = await this.convertAmount(originalAmount, fromCurrency, toCurrency);
                this.paymentAmountInput.value = convertedAmount;
            }
            
            // Convert each item's price
            const itemRows = this.itemsContainer.querySelectorAll('.invoice-item');
            
            for (const itemRow of itemRows) {
                const priceInput = itemRow.querySelector('.item-price');
                if (priceInput) {
                    // Check if this row has requested conversion (from currency select change)
                    const conversionRequested = itemRow.dataset.conversionRequested === 'true';
                    let sourceCurrency = fromCurrency;
                    
                    // Use stored currency information when available
                    if (conversionRequested) {
                        sourceCurrency = itemRow.dataset.previousCurrency || fromCurrency;
                        console.log(`Using stored previous currency for row: ${sourceCurrency}`);
                        
                        // Clear the conversion requested flag
                        delete itemRow.dataset.conversionRequested;
                        delete itemRow.dataset.previousCurrency;
                        delete itemRow.dataset.newCurrency;
                    } else {
                        // For global currency change, use the current currency stored with the row if available
                        sourceCurrency = itemRow.dataset.currentCurrency || fromCurrency;
                        console.log(`Using stored current currency for row: ${sourceCurrency}`);
                    }
                    
                    // Get current price from the input
                    const currentPrice = parseFloat(priceInput.value) || 0;
                    
                    console.log(`Converting price for row: ${currentPrice} ${sourceCurrency} to ${toCurrency}`);
                    const convertedPrice = await this.convertAmount(currentPrice, sourceCurrency, toCurrency);
                    
                    console.log(`Converted price: ${currentPrice} ${sourceCurrency} -> ${convertedPrice} ${toCurrency}`);
                    priceInput.value = convertedPrice;
                    
                    // Update stored currency and price
                    itemRow.dataset.currentCurrency = toCurrency;
                    itemRow.dataset.currentPrice = convertedPrice;
                    
                    // Trigger price recalculation
                    this.recalculateItemPrice(itemRow);
                }
            }
            
            // Update total amount
            this.updateTotalAmount();
        } finally {
            // Hide loading state
            this.setLoadingState(false);
        }
    }
    
    /**
     * Recalculate price for a specific item row
     * @param {HTMLElement} itemRow 
     */
    recalculateItemPrice(itemRow) {
        // Ensure we have an active row
        if (!itemRow || !itemRow.querySelector) {
            console.error('Invalid item row for recalculation', itemRow);
            return;
        }
        
        console.log('Recalculating price for row:', itemRow);
        
        // Get fresh references to the invoice item manager
        const itemManager = window.invoiceItemManager;
        if (!itemManager) {
            console.error('InvoiceItemManager not found');
            return;
        }
        
        // Get a fresh reference to the DOM element to ensure we're working with current data
        const rowIndex = Array.from(itemManager.itemsContainer.children).indexOf(itemRow);
        if (rowIndex === -1) {
            console.error('Row not found in item container');
            return;
        }
        
        // Get fresh reference to the element
        const freshItemRow = itemManager.itemsContainer.children[rowIndex];
        
        // Trigger calculation with the fresh element reference
        itemManager.calculateItemPrice(freshItemRow);
        
        console.log('Price recalculation complete');
    }
    
    /**
     * Update total amount for the invoice
     */
    updateTotalAmount() {
        // Find and use the existing calculation logic
        const itemManager = window.invoiceItemManager;
        if (itemManager && typeof itemManager.updateTotalAmount === 'function') {
            itemManager.updateTotalAmount();
            itemManager.updateJsonData();
        } else {
            console.warn('InvoiceItemManager not found, cannot update total amount');
        }
    }
    
    /**
     * Set loading state during currency conversion
     * @param {boolean} isLoading 
     */
    setLoadingState(isLoading) {
        const currencySelects = document.querySelectorAll('#payment_currency, .item-currency');
        const formContainer = document.querySelector('.invoice-form');
        
        if (isLoading) {
            currencySelects.forEach(select => {
                select.disabled = true;
            });
            
            if (formContainer) {
                formContainer.classList.add('loading');
            }
        } else {
            currencySelects.forEach(select => {
                select.disabled = false;
            });
            
            if (formContainer) {
                formContainer.classList.remove('loading');
            }
        }
    }
}
