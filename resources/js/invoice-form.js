// Invoice form initialization script
// It initializes various components related to the invoice form.
// It handles the initialization of item management, form validation, 
// invoice number generation, and guest invoice modal.
import InvoiceItemManager from './invoice-item-manager';
import InvoiceFormValidation from './invoice-form-validation';
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
    // It self-initializes if required elements are found
    
    // If we are on edit page, try to load existing data from invoice_text field
    if (document.getElementById('invoice_text_json')) {
        const existingDataEl = document.getElementById('invoice_text_json');
        
        // First check if there's hardcoded data in the page (from blade template)
        // In edit mode, Laravel might have injected the data via old() helper or directly from $invoice
        if (window.existingInvoiceData) {
            // Use the globally defined invoice data variable
            try {
                const parsedData = typeof window.existingInvoiceData === 'string' 
                    ? JSON.parse(window.existingInvoiceData) 
                    : window.existingInvoiceData;
                
                // Set to hidden input to ensure it's available for the form
                existingDataEl.value = typeof parsedData === 'string' ? parsedData : JSON.stringify(parsedData);
                
                console.log('Loaded existing invoice data from global variable:', parsedData);

                // Reload items with the existing data
                itemManager.loadExistingData(parsedData);
            } catch (e) {
                console.error('Failed to parse existing invoice data:', e);
            }
        } else if (existingDataEl.value) {
            // If the hidden input already has value (from old() form), use that
            try {
                const parsedData = JSON.parse(existingDataEl.value);
                itemManager.loadExistingData(parsedData);
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
    
    // Initialisation of the form validation
    const validation = new InvoiceFormValidation({
        supplierRequired: document.querySelector('form')?.dataset.supplierRequired || 'Supplier is required',
        clientRequired: document.querySelector('form')?.dataset.clientRequired || 'Client is required',
        amountRequired: document.querySelector('form')?.dataset.amountRequired || 'Amount is required',
        amountNumeric: document.querySelector('form')?.dataset.amountNumeric || 'Amount must be numeric'
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
    document.querySelector('form')?.addEventListener('submit', function() {
        itemManager.updateJsonData();
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
