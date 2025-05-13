// Toggles fields for client and supplier entities
// based on the selected entity in a dropdown menu.
// Toggles the edit link and required field visibility for client_id and supplier_id selects.
export default class EntityFieldsToggle {
    constructor(options = {}) {
        this.clientSelect = null;
        this.clientFields = null;
        this.clientNameRequired = null;
        this.editClientLink = null;
        
        this.supplierSelect = null;
        this.supplierFields = null;
        this.supplierNameRequired = null;
        this.editSupplierLink = null;
        
        this.baseClientEditUrl = options.baseClientEditUrl || '';
        this.baseSupplierEditUrl = options.baseSupplierEditUrl || '';
        this.currentLocale = options.currentLocale || 'cs';
        
        // Flag to prevent infinite loops in case of circular dependencies
        this.isUpdatingClient = false;
        this.isUpdatingSupplier = false;
    }
    
    init() {
        // Initialize client elements
        this.clientSelect = document.getElementById('client_id');
        this.clientFields = document.querySelectorAll('.client-field');
        this.clientNameRequired = document.getElementById('client-name-required');
        this.editClientLink = document.getElementById('edit-client-link');
        
        // Initialize supplier elements
        this.supplierSelect = document.getElementById('supplier_id');
        this.supplierFields = document.querySelectorAll('.supplier-field');
        this.supplierNameRequired = document.getElementById('supplier-name-required');
        this.editSupplierLink = document.getElementById('edit-supplier-link');
        
        // Log initialization status for debugging
        console.log('EntityFieldsToggle initialized with:', {
            clientSelect: this.clientSelect ? 'Found' : 'Not found',
            clientFields: this.clientFields?.length || 0,
            clientNameRequired: this.clientNameRequired ? 'Found' : 'Not found',
            editClientLink: this.editClientLink ? 'Found' : 'Not found',
            supplierSelect: this.supplierSelect ? 'Found' : 'Not found',
            supplierFields: this.supplierFields?.length || 0,
            supplierNameRequired: this.supplierNameRequired ? 'Found' : 'Not found',
            editSupplierLink: this.editSupplierLink ? 'Found' : 'Not found',
        });
        
        // Set event listeners if elements exist
        if (this.clientSelect) {
            this.toggleClientFields();
            this.clientSelect.addEventListener('change', () => this.toggleClientFields());
        }
        
        if (this.supplierSelect) {
            this.toggleSupplierFields();
            this.supplierSelect.addEventListener('change', () => this.toggleSupplierFields());
        }
    }
    
    // This function toggles the fields based on the selected entity
    toggleFields(select, fields, required, editLink, baseUrl, type) {
        if (!select || !fields.length) {
            console.warn(`Cannot toggle ${type} fields - some elements are missing`);
            return;
        }
        
        const selectedId = select.value;
        
        if (selectedId) {
            // Set fields to readonly
            fields.forEach(field => {
                field.readOnly = true;
                field.classList.add('bg-gray-200', 'text-gray-500');
                console.log('Field set to readonly:', field.name + ' - ' + field.value);
            });
            
            // Activate edit link if available
            if (editLink) {
                editLink.classList.remove('pointer-events-none', 'bg-gray-200', 'text-gray-500', 'opacity-50');
                
                // Create URL for edit link
                if (baseUrl) {
                    let editUrl = baseUrl.replace(':id', selectedId);
                    editLink.href = editUrl;
                } else {
                    // Use default path for entity type
                    const path = type === 'Client' ? 'client' : 'supplier';
                    editLink.href = `/${this.currentLocale}/${path}/edit/${selectedId}`;
                }
            }
            
            // Hide required field asterisk if available
            if (required) {
                required.classList.add('hidden');
            }
        } else {
            // Return fields to editable state
            fields.forEach(field => {
                field.readOnly = false;
                field.classList.remove('bg-gray-200', 'text-gray-500');
            });
            
            // Deactivate edit link if available
            if (editLink) {
                editLink.classList.add('pointer-events-none', 'bg-gray-200', 'text-gray-500', 'opacity-50');
                editLink.href = '#';
            }
            
            // Show required field asterisk if available
            if (required) {
                required.classList.remove('hidden');
            }
        }
    }
    
    // Function to toggle client fields
    toggleClientFields() {
        // Prevent recursive calls
        if (this.isUpdatingClient) return;
        
        this.isUpdatingClient = true;
        console.log('EntityFieldsToggle: Toggling client fields, current value:', this.clientSelect?.value);
        
        // Ensure all required elements exist
        if (!this.clientSelect || !this.clientFields.length) {
            console.warn("Client fields toggle: Missing elements");
            this.isUpdatingClient = false;
            return;
        }
        
        this.toggleFields(
            this.clientSelect, 
            this.clientFields, 
            this.clientNameRequired, 
            this.editClientLink, 
            this.baseClientEditUrl,
            'Client'
        );
        
        this.isUpdatingClient = false;
    }
    
    // Function to toggle supplier fields
    toggleSupplierFields() {
        // Prevent recursive calls
        if (this.isUpdatingSupplier) return;
        
        this.isUpdatingSupplier = true;
        console.log('EntityFieldsToggle: Toggling supplier fields, current value:', this.supplierSelect?.value);
        
        // Ensure all required elements exist
        if (!this.supplierSelect || !this.supplierFields.length) {
            console.warn("Supplier fields toggle: Missing elements");
            this.isUpdatingSupplier = false;
            return;
        }
        
        this.toggleFields(
            this.supplierSelect, 
            this.supplierFields, 
            this.supplierNameRequired, 
            this.editSupplierLink, 
            this.baseSupplierEditUrl,
            'Supplier'
        );
        
        this.isUpdatingSupplier = false;
    }
    
    // Method to manually apply toggles after loading data
    applyToggles() {
        console.log('EntityFieldsToggle: Manually applying toggles');
        console.log('Client select value:', this.clientSelect?.value);
        console.log('Supplier select value:', this.supplierSelect?.value);
        
        // Log all supplier fields to verify they exist and their content
        if (this.supplierFields && this.supplierFields.length > 0) {
            console.log('Supplier fields found:', this.supplierFields.length);
            this.supplierFields.forEach((field, index) => {
                console.log(`Supplier field ${index} (${field.id || 'no-id'})`, {
                    name: field.name,
                    value: field.value,
                    readOnly: field.readOnly,
                    classes: Array.from(field.classList)
                });
            });
        } else {
            console.warn('No supplier fields found for toggling');
        }
        
        // Applying toggles with a small delay to ensure all DOM updates are finished
        setTimeout(() => {
            if (this.clientSelect?.value) {
                console.log('EntityFieldsToggle: Manually applying client toggle for ID:', this.clientSelect.value);
                this.toggleClientFields();
            }
            
            if (this.supplierSelect?.value) {
                console.log('EntityFieldsToggle: Manually applying supplier toggle for ID:', this.supplierSelect.value);
                this.toggleSupplierFields();
            }
        }, 150); // Slightly longer timeout for more reliability
    }
}
