// Invoice Item Manager
// This class manages the invoice items in the invoice form.
// It handles adding, removing, and updating items, as well as calculating totals and updating JSON data.
// It also handles the display of the total amount and the currency selection.

export default class InvoiceItemManager {
    constructor() {
        this.itemsContainer = null;
        this.addButton = null;
        this.itemTemplate = null;
        this.jsonInput = null;
        this.noteInput = null;
        this.paymentAmountInput = null;
        this.totalDisplay = null;
        this.currencySelect = null;
        this.productModal = null;
        this.activeItemRow = null;
        console.log('InvoiceItemManager initialized');
    }
    
    init() {
        // References to container elements and buttons
        this.itemsContainer = document.getElementById('invoice-items-list');
        this.addButton = document.getElementById('add-invoice-item');
        this.itemTemplate = document.querySelector('.invoice-item-template').innerHTML;
        this.jsonInput = document.getElementById('invoice_text_json');
        this.noteInput = document.getElementById('invoice_note');
        this.paymentAmountInput = document.getElementById('payment_amount');
        this.totalDisplay = document.getElementById('invoice-items-total');
        this.currencySelect = document.getElementById('payment_currency');
        this.productModal = document.getElementById('product-selection-modal');
        
        if (!this.itemsContainer || !this.addButton || !this.itemTemplate) {
            console.warn('Some invoice item elements not found. Invoice items functionality might be limited.');
            return;
        }
        
        // Event listeners
        this.addButton.addEventListener('click', () => this.addItem());
        
        // Event delegation for buttons and value changes
        this.itemsContainer.addEventListener('click', this.handleItemButtonClicks.bind(this));
        this.itemsContainer.addEventListener('input', this.handleItemInputChanges.bind(this));
        this.itemsContainer.addEventListener('change', this.handleItemSelectChanges.bind(this));
        
        // Listeners for note input
        if (this.noteInput) {
            this.noteInput.addEventListener('input', () => this.updateJsonData());
        }
        
        // Listeners for currency selection
        if (this.currencySelect) {
            this.currencySelect.addEventListener('change', () => this.updateTotalDisplay());
        }

        // Initialize product selection modal
        this.initializeProductSelectionModal();
        
        // Loading existing data
        this.loadExistingData();
        
        // Add the first item if none exists
        if (this.itemsContainer.children.length === 0) {
            this.addItem();
        }
    }

    initializeProductSelectionModal() {
        if (!this.productModal) {
            console.log('Product modal not found');
            return;
        }
        console.log('Initializing product selection modal');

        // Close button handler
        const closeButtons = this.productModal.querySelectorAll('.close-modal');
        closeButtons.forEach(button => {
            button.addEventListener('click', () => {
                this.closeProductModal();
            });
        });
        
        // Multiple event listeners to ensure we catch the event regardless of how it's dispatched
    
    // 1. Standard Livewire 3 event listener
    document.addEventListener('livewire:initialized', () => {
        console.log('Livewire initialized, setting up event listeners');
    });
    
    // 2. Direct event listener on window (this should catch our manual dispatch)
    window.addEventListener('product-selected', (event) => {
        console.log('Product selected event captured on window:', event);
        
        try {
            let productData = null;
            
            if (event.detail && event.detail.productData) {
                productData = event.detail.productData;
                console.log('Found product data in event.detail.productData:', productData);
            } else if (event.detail) {
                productData = event.detail;
                console.log('Found product data directly in event.detail:', productData);
            }
            
            if (productData && (productData.id || productData.name)) {
                console.log('Valid product data found, processing selection...');
                this.handleProductSelection(productData);
                this.closeProductModal();
            } else {
                console.error('Invalid product data structure:', event.detail);
            }
        } catch (error) {
            console.error('Error processing product selection:', error);
        }
    });
    
    // 3. Direct document event listener (fallback)
    document.addEventListener('product-selected', (event) => {
        console.log('Product selected event captured on document:', event);
        // Same processing logic as above
        try {
            let productData = null;
            
            if (event.detail && event.detail.productData) {
                productData = event.detail.productData;
                console.log('Found product data in event.detail.productData:', productData);
            } else if (event.detail) {
                productData = event.detail;
                console.log('Found product data directly in event.detail:', productData);
            }
            
            if (productData && (productData.id || productData.name)) {
                console.log('Valid product data found, processing selection...');
                this.handleProductSelection(productData);
                this.closeProductModal();
            } else {
                console.error('Invalid product data structure:', event.detail);
            }
        } catch (error) {
            console.error('Error processing product selection from document event:', error);
        }
    });

    // Close modal on outside click
    this.productModal.addEventListener('click', (e) => {
        if (e.target === this.productModal) {
            this.closeProductModal();
        }
    });
    }
    
    handleItemButtonClicks(e) {
        // Removing item
        if (e.target.classList.contains('remove-item') || e.target.parentElement.classList.contains('remove-item')) {
            const itemElement = e.target.closest('.invoice-item');
            if (itemElement) {
                itemElement.remove();
                this.updateJsonData();
                this.updateTotalAmount();
            }
        }
        
        // Duplicating item
        if (e.target.classList.contains('duplicate-item') || e.target.parentElement.classList.contains('duplicate-item')) {
            const itemElement = e.target.closest('.invoice-item');
            if (itemElement) {
                const nameValue = itemElement.querySelector('.item-name').value;
                const quantityValue = itemElement.querySelector('.item-quantity').value;
                const unitValue = itemElement.querySelector('.item-unit').value;
                const priceValue = itemElement.querySelector('.item-price').value;
                const currencyValue = itemElement.querySelector('.item-currency').value;
                const taxValue = itemElement.querySelector('.item-tax').value;
                const productId = itemElement.dataset.productId || null;
                
                this.addItem(nameValue, quantityValue, unitValue, priceValue, taxValue, productId, currencyValue);
            }
        }

        // Product selection button handling - FIXED IMPLEMENTATION
        if (e.target.classList.contains('select-product') || e.target.parentElement.classList.contains('select-product')) {
            const itemElement = e.target.closest('.invoice-item');
            if (itemElement) {
                console.log('Product selection button clicked for row:', itemElement);
                
                // Store reference to the active row before opening modal
                this.activeItemRow = itemElement;
                
                // Mark the row as active with a visual indicator
                const allRows = this.itemsContainer.querySelectorAll('.invoice-item');
                allRows.forEach(row => row.classList.remove('bg-blue-50'));
                itemElement.classList.add('bg-blue-50');
                
                this.openProductModal();
            } else {
                console.error('Could not find invoice-item parent for the clicked button');
            }
        }
    }
    
    handleItemInputChanges(e) {
        if (e.target.classList.contains('item-quantity') || 
            e.target.classList.contains('item-price') ||
            e.target.classList.contains('item-tax')) {
                
            this.calculateItemPrice(e.target.closest('.invoice-item'));
            this.updateJsonData();
            this.updateTotalAmount();
        } else {
            this.updateJsonData();
        }
    }
    
    handleItemSelectChanges(e) {
        const itemRow = e.target.closest('.invoice-item');
        if (!itemRow) {
            console.error('Could not find parent invoice-item for select change event');
            return;
        }
        
        if (e.target.classList.contains('item-tax')) {
            console.log('Tax value changed, recalculating price');
            this.calculateItemPrice(itemRow);
            this.updateJsonData();
            this.updateTotalAmount();
        } else if (e.target.classList.contains('item-currency')) {
            console.log('Currency value changed', e.target.value);
            
            // Get the previous and new currency
            const previousCurrency = itemRow.dataset.currentCurrency || 'CZK';
            const newCurrency = e.target.value;
            
            console.log(`Currency changed from ${previousCurrency} to ${newCurrency}`);
            
            // If currency manager is available, let it handle the currency change
            if (window.currencyManager) {
                console.log('Using CurrencyManager for conversion');
                
                // Store information about the change that's about to happen
                itemRow.dataset.previousCurrency = previousCurrency;
                itemRow.dataset.newCurrency = newCurrency;
                itemRow.dataset.conversionRequested = 'true';
                
                // CurrencyManager will handle the conversion
            } else {
                console.log('No CurrencyManager available, using basic recalculation');
                // Force a fresh calculation using the current DOM state
                setTimeout(() => {
                    const freshItemRow = e.target.closest('.invoice-item');
                    this.calculateItemPrice(freshItemRow);
                    this.updateJsonData();
                    this.updateTotalAmount();
                }, 0);
            }
        } else {
            console.log('Other select field changed, updating JSON data');
            this.updateJsonData();
        }
    }
    
    // Add a new item to the invoice
    addItem(name = '', quantity = '1', unit = 'pieces', price = '0', tax = '0', productId = null, currency = null) {
        const itemWrapper = document.createElement('div');
        itemWrapper.innerHTML = this.itemTemplate;
        const itemElement = itemWrapper.firstElementChild;
        
        // Set values if provided
        if (name) itemElement.querySelector('.item-name').value = name;
        if (quantity) itemElement.querySelector('.item-quantity').value = quantity;
        if (unit) itemElement.querySelector('.item-unit').value = unit;
        if (price) itemElement.querySelector('.item-price').value = price;

        // Save product_id if provided
        if (productId) {
            itemElement.dataset.productId = productId;
        }
        
        // Set currency if provided or use the payment currency
        const currencySelect = itemElement.querySelector('.item-currency');
        if (currencySelect) {
            const currencyToUse = currency || (this.currencySelect ? this.currencySelect.value : 'CZK');
            
            // Find option with value currency and set it as selected
            for (let i = 0; i < currencySelect.options.length; i++) {
                if (currencySelect.options[i].value === currencyToUse) {
                    currencySelect.selectedIndex = i;
                    break;
                }
            }
            
            // Store the current currency in data attribute
            itemElement.dataset.currentCurrency = currencyToUse;
        }
        
        // Store the current price in data attribute
        itemElement.dataset.currentPrice = price;

        if (tax) {
            const taxSelect = itemElement.querySelector('.item-tax');
            // Find option with value tax and set it as selected
            for (let i = 0; i < taxSelect.options.length; i++) {
                if (taxSelect.options[i].value === tax) {
                    taxSelect.selectedIndex = i;
                    break;
                }
            }
        }
        
        this.itemsContainer.appendChild(itemElement);
        this.calculateItemPrice(itemElement);
        this.updateJsonData();
        this.updateTotalAmount();
    }
    
    // Calculate total item price
    calculateItemPrice(itemElement) {
        // Ensure we have the actual DOM element, not a cached reference
        if (!itemElement || !itemElement.querySelector) {
            console.error('Invalid item element provided to calculateItemPrice:', itemElement);
            return;
        }
        
        // Force access current DOM values instead of potentially stale data
        const quantityInput = itemElement.querySelector('.item-quantity');
        const priceInput = itemElement.querySelector('.item-price');
        const taxRateSelect = itemElement.querySelector('.item-tax');
        const priceCompleteInput = itemElement.querySelector('.item-price-complete');
        const currencySelect = itemElement.querySelector('.item-currency');
        
        // Verify all required elements are found
        if (!quantityInput || !priceInput || !taxRateSelect || !priceCompleteInput || !currencySelect) {
            console.error('Required elements not found in item element:', {
                quantityInput,
                priceInput,
                taxRateSelect,
                priceCompleteInput,
                currencySelect
            });
            return;
        }
        
        // Get current values directly from DOM
        const quantity = parseFloat(quantityInput.value) || 0;
        const price = parseFloat(priceInput.value) || 0;
        const taxRate = parseFloat(taxRateSelect.value) || 0;
        const currency = currencySelect.value;
        
        // Store the current currency and price in data attributes for future reference
        itemElement.dataset.currentCurrency = currency;
        itemElement.dataset.currentPrice = price;
        
        console.log('Calculating item price with values:', {
            quantity,
            price, 
            taxRate,
            currency,
            itemElement: itemElement.outerHTML.slice(0, 100) + '...' // Log partial HTML for debugging
        });
        
        // Calculate price with VAT
        const totalWithoutTax = quantity * price;
        const totalWithTax = totalWithoutTax * (1 + (taxRate / 100));
        
        // Round to 2 decimal places
        const totalRounded = Math.round(totalWithTax * 100) / 100;
        
        // Format number with thousand separator and 2 decimal places
        const formattedTotal = this.formatNumber(totalRounded);
        
        // Set resulting value to the price complete field
        priceCompleteInput.value = formattedTotal;
        
        console.log('Item price calculation completed:', {
            totalWithoutTax,
            totalWithTax,
            totalRounded,
            formattedTotal,
            storedCurrency: itemElement.dataset.currentCurrency,
            storedPrice: itemElement.dataset.currentPrice
        });
    }
    
    // Update total invoice amount
    updateTotalAmount() {
        let total = 0;
        let hasNonZeroPrices = false;
        
        // Iterate through all items and sum their prices
        const itemElements = this.itemsContainer.querySelectorAll('.invoice-item');
        itemElements.forEach(itemElement => {
            const quantity = parseFloat(itemElement.querySelector('.item-quantity').value) || 0;
            const price = parseFloat(itemElement.querySelector('.item-price').value) || 0;
            const taxRate = parseFloat(itemElement.querySelector('.item-tax').value) || 0;
            
            const itemTotal = quantity * price * (1 + (taxRate / 100));
            total += itemTotal;
            
            // Check if there are non-zero prices
            if (price > 0) {
                hasNonZeroPrices = true;
            }
        });
        
        // Round to 2 decimal places
        const roundedTotal = Math.round(total * 100) / 100;
        
        // Set readonly for total invoice amount if there are items with non-zero price
        if (this.paymentAmountInput) {
            if (hasNonZeroPrices) {
                this.paymentAmountInput.value = roundedTotal;
                this.paymentAmountInput.readOnly = true;
                this.paymentAmountInput.classList.add('bg-[#FDFDFC]', 'bg-gray-200', 'text-gray-500');
            } else {
                this.paymentAmountInput.readOnly = false;
                this.paymentAmountInput.classList.remove('bg-[#FDFDFC]', 'bg-gray-200', 'text-gray-500');
            }
        }
        
        // Update displayed total amount
        this.updateTotalDisplay(roundedTotal);
    }
    
    // Update total display
    updateTotalDisplay(total = null) {
        if (total === null && this.paymentAmountInput) {
            total = parseFloat(this.paymentAmountInput.value) || 0;
        }
        
        const formattedTotal = this.formatNumber(total);
        const currency = this.currencySelect ? this.currencySelect.value : 'CZK';
        
        if (this.totalDisplay) {
            this.totalDisplay.textContent = `${formattedTotal} ${currency}`;
        }
    }
    
    // Format number for display
    formatNumber(number) {
        return number.toLocaleString('cs-CZ', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
    
    // Update JSON data
    updateJsonData() {
        if (!this.jsonInput) return;
        
        const items = [];
        const itemElements = this.itemsContainer.querySelectorAll('.invoice-item');

        itemElements.forEach(itemElement => {
            const name = itemElement.querySelector('.item-name').value.trim();
            const quantity = itemElement.querySelector('.item-quantity').value.trim();
            const unit = itemElement.querySelector('.item-unit').value.trim();
            const price = itemElement.querySelector('.item-price').value.trim();
            const currency = itemElement.querySelector('.item-currency').value.trim();
            const tax = itemElement.querySelector('.item-tax').value.trim();
            const priceComplete = itemElement.querySelector('.item-price-complete').value.trim();
        
            // Add product_id only if available
            const productId = itemElement.dataset.productId || null;
            
            if (name || quantity || price) {
                const item = {
                    name,
                    quantity,
                    unit,
                    price,
                    currency,
                    tax,
                    priceComplete
                };
                
                // Add product ID if available
                if (productId) {
                    item.product_id = productId;
                }
            
                items.push(item);
            }
        });
        
        const jsonData = {
            items,
            note: this.noteInput ? this.noteInput.value.trim() : ''
        };
        
        this.jsonInput.value = JSON.stringify(jsonData);
    }
    
    // Load existing data
    loadExistingData(existingData = null) {
        try {
            let jsonData;
            
            if (existingData) {
                jsonData = existingData;
            } else if (this.jsonInput && this.jsonInput.value) {
                try {
                    // Try parsing as JSON if it's a string
                    if (typeof this.jsonInput.value === 'string') {
                        jsonData = JSON.parse(this.jsonInput.value);
                    } else {
                        jsonData = this.jsonInput.value;
                    }
                } catch (e) {
                    console.error('Error parsing JSON from input:', e);
                    jsonData = null;
                }
            }
            
            // Check if item data exists
            if (jsonData && jsonData.items && Array.isArray(jsonData.items)) {
                // Clear existing items
                this.itemsContainer.innerHTML = '';
                
                // Add items from JSON
                jsonData.items.forEach(item => {
                    this.addItem(
                        item.name || '', 
                        item.quantity || '1',
                        item.unit || 'pieces',
                        item.price || '0',
                        item.tax || '0',
                        item.product_id || null,
                        item.currency || 'CZK'
                    );
                });
                
                // Update total amount
                this.updateTotalAmount();
            } else {
                // Add at least one empty item
                this.addItem();
            }
            
            // Set note if it exists
            if (jsonData && jsonData.note && this.noteInput) {
                this.noteInput.value = jsonData.note;
            }
            
            // Update JSON data
            this.updateJsonData();
            
        } catch (error) {
            console.error('Error loading existing data:', error);
            // Add at least one empty item
            this.addItem();
        }
    }

    // Open product selection modal
    openProductModal() {
        if (!this.productModal) {
            console.error('Product modal not found in DOM');
            return;
        }
        
        // Debug check if active row is set
        if (!this.activeItemRow) {
            console.warn('Opening product modal but no active row is set');
        } else {
            console.log('Opening product modal with active row:', this.activeItemRow);
        }
        
        // Store a reference to the active row in the modal element itself as a fallback
        if (this.activeItemRow) {
            this.productModal.dataset.activeRowIndex = Array.from(
                this.itemsContainer.querySelectorAll('.invoice-item')
            ).indexOf(this.activeItemRow);
        }
        
        this.productModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden'); // Prevent scrolling
    }

    // Close product selection modal
    closeProductModal() {
        if (!this.productModal) return;
    
        this.productModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden'); // Enable scrolling
        
        // Remove the visual indicator from rows
        const allRows = this.itemsContainer.querySelectorAll('.invoice-item');
        allRows.forEach(row => row.classList.remove('bg-blue-50'));
    }

    logProductData(data) {
        console.group('Product Data Analysis');
        console.log('Raw product data:', data);
        
        // Check data structure
        console.log('Data structure:');
        console.log('- id:', typeof data.id, data.id);
        console.log('- name:', typeof data.name, data.name);
        console.log('- price:', typeof data.price, data.price);
        console.log('- tax_rate:', typeof data.tax_rate, data.tax_rate);
        console.log('- unit:', typeof data.unit, data.unit);
        console.log('- currency:', typeof data.currency, data.currency);
        
        // Check if active row exists
        console.log('Active row:', this.activeItemRow ? 'Found' : 'Missing');
        
        // Check form field existence
        if (this.activeItemRow) {
            const fields = {
                name: this.activeItemRow.querySelector('.item-name'),
                price: this.activeItemRow.querySelector('.item-price'),
                tax: this.activeItemRow.querySelector('.item-tax'),
                unit: this.activeItemRow.querySelector('.item-unit'),
                currency: this.activeItemRow.querySelector('.item-currency')
            };
            
            console.log('Form fields found:');
            for (const [fieldName, element] of Object.entries(fields)) {
                console.log(`- ${fieldName}: ${element ? 'Found' : 'Missing'}`);
            }
        }
        
        console.groupEnd();
    }

    // Handle product selection from modal
    async handleProductSelection(data) {
        try {
            this.logProductData(data);

            console.log('Updating fields with complete product data:', data);
            if (!this.activeItemRow) {
                console.error('No active row set for product selection');
                return;
            }

            // Set product ID in data attribute
            this.activeItemRow.dataset.productId = data.id;

            // Set product name
            const nameInput = this.activeItemRow.querySelector('.item-name');
            if (nameInput) {
                nameInput.value = data.name;
            }

            // Get current currency from the payment field
            const currentCurrency = document.getElementById('payment_currency')?.value || 'CZK';
            
            // Get product's original currency and price
            const productCurrency = data.currency || 'CZK';
            const productPrice = parseFloat(data.price) || 0;

            // Set product price, converting if necessary
            const priceInput = this.activeItemRow.querySelector('.item-price');
            if (priceInput) {
                // Check if we need to convert the price
                if (productCurrency !== currentCurrency && window.currencyManager) {
                    try {
                        // Use currency manager to convert the price
                        const convertedPrice = await window.currencyManager.convertAmount(
                            productPrice, 
                            productCurrency, 
                            currentCurrency
                        );
                        
                        console.log(`Converting price from ${productPrice} ${productCurrency} to ${convertedPrice} ${currentCurrency}`);
                        priceInput.value = convertedPrice;
                    } catch (error) {
                        console.error('Failed to convert product price:', error);
                        priceInput.value = productPrice;
                    }
                } else {
                    // No conversion needed
                    priceInput.value = productPrice;
                }
            }

            // Set currency to match current invoice currency
            const currencySelect = this.activeItemRow.querySelector('.item-currency');
            if (currencySelect) {
                currencySelect.value = currentCurrency;
            }

            // // Set other product fields if they exist in the data
            // if (data.tax !== undefined) {
            //     const taxSelect = this.activeItemRow.querySelector('.item-tax');
            //     if (taxSelect) taxSelect.value = data.tax;
            // }
            // Update tax rate field
            const taxField = this.activeItemRow.querySelector('.item-tax');
            if (taxField && data.tax_rate !== undefined && data.tax_rate !== null) {
                console.log('Setting tax rate:', data.tax_rate);
                const taxRate = parseFloat(data.tax_rate);
                
                // Find option with closest tax rate value
                const options = taxField.options;
                let bestMatch = 0;
                let minDiff = Number.MAX_VALUE;
                
                for (let i = 0; i < options.length; i++) {
                    const diff = Math.abs(parseFloat(options[i].value) - taxRate);
                    if (diff < minDiff) {
                        minDiff = diff;
                        bestMatch = i;
                    }
                }
                
                taxField.selectedIndex = bestMatch;
                console.log(`Set tax rate to option index ${bestMatch} with value ${options[bestMatch].value}`);
            }

            if (data.unit) {
                const unitInput = this.activeItemRow.querySelector('.item-unit');
                if (unitInput) unitInput.value = data.unit;
            }

            // Recalculate the item price based on quantity and tax
            this.calculateItemPrice(this.activeItemRow);

            // Update the JSON data and total
            this.updateJsonData();
            this.updateTotalAmount();

            // Close the modal
            this.closeProductModal();
        } catch (error) {
            console.error('Error processing product selection:', error);
        }
    }
}
