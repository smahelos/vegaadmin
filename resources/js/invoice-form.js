// Invoice form initialization script
// It initializes various components related to the invoice form.
// It handles the initialization of item management, form validation, 
// invoice number generation, and guest invoice modal.
import InvoiceItemManager from './invoice-item-manager';
import InvoiceFormValidationExtended from './invoice-form-validation-extended';
import InvoiceNumberGenerator from './invoice-number-generator';
import GuestInvoiceModal from './guest-invoice-modal';
import SupplierFormData from './supplier-form-data.js';
import ClientFormData from './client-form-data.js';
import CurrencyManager from './currency-manager.js';

document.addEventListener('DOMContentLoaded', function() {
    // Initialisation of the invoice item manager
    const itemManager = new InvoiceItemManager();
    itemManager.init();
    
    // Store reference to item manager globally for use by other components
    window.invoiceItemManager = itemManager;
    
    // Initialize currency manager
    const currencyManager = new CurrencyManager();
    // Export to global scope
    window.currencyManager = currencyManager;
    
    // If we are on edit page, try to load existing data from invoice_text field
    if (document.getElementById('invoice-edit-form')) {
        const existingDataEl = document.getElementById('invoice-products');
        
        // First check if there's hardcoded data in the page (from blade template)
        if (window.existingInvoiceData && Array.isArray(window.existingInvoiceData)) {
            // If existingInvoiceData is already an array, use it directly
            console.log('Loaded existing invoice data from global array:', window.existingInvoiceData);
            
            // Set to hidden input to ensure it's available for the form
            const productsInput = document.getElementById('invoice-products');
            if (productsInput) {
                productsInput.value = JSON.stringify(window.existingInvoiceData);
            }

            console.log('Existing invoice data is an array:', window.existingInvoiceData);

            // Reload items with the existing data
            itemManager.loadExistingData({ items: window.existingInvoiceData });
            
        } else if (typeof window.existingInvoiceData === 'string') {
            // If it's a string, try to parse it
            try {
                const parsedData = JSON.parse(window.existingInvoiceData);
                
                // Set to hidden input to ensure it's available for the form
                existingDataEl.value = window.existingInvoiceData;

                // Set to hidden input to ensure it's available for the form
                const productsInput = document.getElementById('invoice-products');
                if (productsInput && Array.isArray(parsedData)) {
                    productsInput.value = window.existingInvoiceData;
                } else if (productsInput && parsedData.items) {
                    productsInput.value = JSON.stringify(parsedData.items);
                }

                console.log('Parsed existing invoice data from string:', parsedData);
                itemManager.loadExistingData(parsedData);
            } catch (e) {
                console.error('Failed to parse existing invoice data:', e);
            }
        } else if (existingDataEl.value) {
            // If the hidden input already has value (from old() form), use that
            try {
                const parsedData = JSON.parse(existingDataEl.value);
                itemManager.loadExistingData(parsedData);

                // Set to hidden input to ensure it's available for the form
                const productsInput = document.getElementById('invoice-products');
                if (productsInput && parsedData.items) {
                    productsInput.value = JSON.stringify(parsedData.items);
                }
            } catch (e) {
                console.error('Failed to parse existing invoice data from input:', e);
            }
        }
        
        // Set note if it exists
        if (document.getElementById('invoice_note')) {
            const noteEl = document.getElementById('invoice_note');
            if (noteEl.value) {
                // If we loaded data but note wasn't part of JSON, make sure it gets included
                setTimeout(() => {
                    itemManager.updateJsonData();
                }, 100);
            }
        }
    }
    
    // Check if user is logged in to initialize client and supplier handling
    const isLoggedIn = document.getElementById('invoice-form')?.dataset.userLoggedIn === 'true';
    
    // Check if user is editing or creating invoice
    const isEditing = document.getElementById('invoice-form')?.dataset.isEditing === 'true';
    
    // Initialize entity fields toggle with correct locale
    const locale = document.querySelector('input[name="lang"]')?.value || 'cs';
    
    // Log all form elements to help with debugging
    console.log('Diagnosing form elements...');
    console.log('isLoggedIn:', isLoggedIn);
    console.log('isEditing:', isEditing);
    
    if (isLoggedIn) {
        if (isEditing) {
            // Initialize module to preselect default supplier and handle supplier form data
            console.log('Initializing supplier form data...');
            SupplierFormData.init({ isEditMode: true });
            // Initialize module to preselect default client and handle client form data
            console.log('Initializing client form data...');
            ClientFormData.init({ isEditMode: true });  
        } else {
            // Initialize module to preselect default supplier and handle supplier form data
            console.log('Initializing supplier form data...');
            SupplierFormData.init();
            // Initialize module to preselect default client and handle client form data
            console.log('Initializing client form data...');
            ClientFormData.init();    
        }
    }
    
    // Initialisation of the form validation with extended capabilities
    const validation = new InvoiceFormValidationExtended({
        supplierRequired: document.querySelector('form')?.dataset.supplierRequired || 'Dodavatel je povinný',
        clientRequired: document.querySelector('form')?.dataset.clientRequired || 'Zákazník je povinný',
        amountRequired: document.querySelector('form')?.dataset.amountRequired || 'Částka je povinná',
        amountNumeric: document.querySelector('form')?.dataset.amountNumeric || 'Částka musí být číslo',
        amountPositive: document.querySelector('form')?.dataset.amountPositive || 'Částka musí být kladná',
        invalidIban: document.querySelector('form')?.dataset.invalidIban || 'Neplatný IBAN',
        invalidSwift: document.querySelector('form')?.dataset.invalidSwift || 'Neplatný SWIFT/BIC kód',
        invalidVatId: document.querySelector('form')?.dataset.invalidVatId || 'Neplatné DIČ',
        invalidBusinessId: document.querySelector('form')?.dataset.invalidBusinessId || 'Neplatné IČ',
        itemNameRequired: document.querySelector('form')?.dataset.itemNameRequired || 'Název položky je povinný'
    });
    validation.init();
    
    // Initialisation of the invoice number generator (only for invoice creation)
    if (document.getElementById('generate-invoice-number')) {
        const numberGenerator = new InvoiceNumberGenerator();
        numberGenerator.init();
    }
    
    // Initialisation of the modal for unauthenticated users
    if (!isLoggedIn) {
        const guestModal = new GuestInvoiceModal({
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.content || '',
            routes: {
                storeGuest: document.querySelector('form')?.action || '/invoice/store/guest'
            },
            messages: {
                processing: document.querySelector('form')?.dataset.processingText || 'Processing...',
                createError: document.querySelector('form')?.dataset.errorText || 'An error occurred while creating the invoice.'
            }
        });
        guestModal.init();
    }
    
    // Ensure JSON data is updated before form submission
    document.querySelector('form')?.addEventListener('submit', function(e) {
        try {
            itemManager.updateJsonData();
            
            // Kontrola, že invoice-products obsahuje data
            const productsInput = document.getElementById('invoice-products');
            if (!productsInput || !productsInput.value) {
                console.error('No product data available for submission');
                
                // Zkusíme ještě jednou nastavit data
                const items = Array.from(document.querySelectorAll('.invoice-item')).map(item => {
                    const name = item.querySelector('.item-name').value.trim();
                    const quantity = parseFloat(item.querySelector('.item-quantity').value) || 0;
                    const unit = item.querySelector('.item-unit').value;
                    const price = parseFloat(item.querySelector('.item-price').value) || 0;
                    const currency = item.querySelector('.item-currency').value;
                    const taxRate = parseFloat(item.querySelector('.item-tax').value) || 0;
                    const productId = item.dataset.productId || null;
                    
                    const product = {
                        name,
                        quantity,
                        unit,
                        price,
                        currency,
                        tax_rate: taxRate
                    };
                    
                    if (productId) {
                        product.product_id = productId;
                    }
                    
                    return product;
                });
                
                if (items.length > 0 && productsInput) {
                    productsInput.value = JSON.stringify(items);
                    console.log('Fixed missing invoice products data before submit:', items);
                }
            }
        } catch (error) {
            console.error('Error updating invoice data before submit:', error);
        }
    });
    
    // Ensure selects have proper value set on page load
    const selects = document.querySelectorAll('select');
    selects.forEach(select => {
        // Get the expected selected value from data attribute
        const selectedValue = select.getAttribute('data-selected') || 
                             select.getAttribute(':selected') || 
                             select.value;
        
        if (selectedValue) {
            // Find matching option
            const option = Array.from(select.options).find(opt => opt.value === selectedValue);
            if (option) {
                option.selected = true;
                select.value = selectedValue;
                console.log(`Initial select ${select.id || select.name} value set to: ${selectedValue}`);
            }
        }
    });
});
