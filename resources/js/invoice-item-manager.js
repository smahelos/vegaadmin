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
        
        // Loading existing data
        this.loadExistingData();
        
        // Add the first item if none exists
        if (this.itemsContainer.children.length === 0) {
            this.addItem();
        }
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
                const taxValue = itemElement.querySelector('.item-tax').value;
                
                this.addItem(nameValue, quantityValue, unitValue, priceValue, taxValue);
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
        if (e.target.classList.contains('item-tax')) {
            this.calculateItemPrice(e.target.closest('.invoice-item'));
            this.updateJsonData();
            this.updateTotalAmount();
        }
    }
    
    // Add a new item to the invoice
    addItem(name = '', quantity = '1', unit = 'ks', price = '0', tax = '0') {
        const itemWrapper = document.createElement('div');
        itemWrapper.innerHTML = this.itemTemplate;
        const itemElement = itemWrapper.firstElementChild;
        
        // Set values if provided
        if (name) itemElement.querySelector('.item-name').value = name;
        if (quantity) itemElement.querySelector('.item-quantity').value = quantity;
        if (unit) itemElement.querySelector('.item-unit').value = unit;
        if (price) itemElement.querySelector('.item-price').value = price;
        
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
        const quantity = parseFloat(itemElement.querySelector('.item-quantity').value) || 0;
        const price = parseFloat(itemElement.querySelector('.item-price').value) || 0;
        const taxRate = parseFloat(itemElement.querySelector('.item-tax').value) || 0;
        
        // Calculate price with VAT
        const totalWithoutTax = quantity * price;
        const totalWithTax = totalWithoutTax * (1 + (taxRate / 100));
        
        // Round to 2 decimal places
        const totalRounded = Math.round(totalWithTax * 100) / 100;
        
        // Format number with thousand separator and 2 decimal places
        const formattedTotal = this.formatNumber(totalRounded);
        
        // Set resulting value to the price complete field
        itemElement.querySelector('.item-price-complete').value = formattedTotal;
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
            const tax = itemElement.querySelector('.item-tax').value.trim();
            const priceComplete = itemElement.querySelector('.item-price-complete').value.trim();
            
            if (name || quantity || price) {
                items.push({
                    name,
                    quantity,
                    unit,
                    price,
                    tax,
                    priceComplete
                });
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
                        item.unit || 'ks',
                        item.price || '0',
                        item.tax || '0'
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
}
