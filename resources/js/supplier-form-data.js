/**
 * Supplier Form Data Module
 * This module handles supplier data population and field management in invoice creation form.
 * It loads default supplier data on page load and handles supplier selection changes.
 */
const SupplierFormData = (function() {
    // Define supplier field IDs for data mapping
    const supplierFields = [
        'name', 'email', 'phone', 'street', 'city', 'zip', 
        'country', 'ico', 'dic', 'account_number', 'bank_code', 
        'bank_name', 'iban', 'swift'
    ];
    
    // Base URL for API requests
    const baseURL = window.location.origin;
    
    // Keep track of current supplier ID
    let currentSupplierId = null;
    
    // Flag to determine if we're in edit mode
    let isEditMode = false;
    
    /**
     * Initialize the module
     */
    function init() {
        console.log('Initializing SupplierFormData module');
        
        // Set edit mode flag
        isEditMode = options.isEditMode || false;
        console.log(`Edit mode: ${isEditMode}`);

        // Load default supplier on page load only if not in edit mode
        if (!isEditMode) {
            loadDefaultSupplier();
        }
        
        // Set up event listener for supplier select change
        const supplierSelect = document.getElementById('supplier_id');
        if (supplierSelect) {
            supplierSelect.addEventListener('change', handleSupplierChange);
            console.log('Event listener for supplier select added');
        } else {
            console.warn('Supplier select element not found');
        }
    }
    
    /**
     * Load default supplier data on page load
     */
    function loadDefaultSupplier() {
        console.log('Loading default supplier data');
        
        fetch(`${baseURL}/api/suppliers/default`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Default supplier data received:', data);
                if (data && data.id) {
                    // Update select with default supplier if it exists
                    const supplierSelect = document.getElementById('supplier_id');
                    if (supplierSelect) {
                        supplierSelect.value = data.id;
                        currentSupplierId = data.id;
                        console.log(`Default supplier set to ID: ${data.id}`);
                    }
                    
                    // Fill form fields with supplier data
                    fillSupplierFields(data);
                    
                    // Set fields as readonly since we have a supplier
                    setSupplierFieldsReadOnly(true);
                    
                    // Update edit supplier link
                    updateEditSupplierLink(data.id);
                } else {
                    console.log('No default supplier found or data is invalid');
                    clearSupplierFields();
                    setSupplierFieldsReadOnly(false);
                    updateEditSupplierLink(null);
                }
            })
            .catch(error => {
                console.error('Error fetching default supplier:', error);
                clearSupplierFields();
                setSupplierFieldsReadOnly(false);
                updateEditSupplierLink(null);
            });
    }
    
    /**
     * Handle supplier select change event
     * @param {Event} event - Change event
     */
    function handleSupplierChange(event) {
        const supplierId = event.target.value;
        console.log(`Supplier changed to ID: ${supplierId}`);
        
        if (!supplierId) {
            console.log('No supplier selected, clearing fields');
            clearSupplierFields();
            setSupplierFieldsReadOnly(false);
            updateEditSupplierLink(null);
            currentSupplierId = null;
            return;
        }
        
        // Fetch supplier data from API
        fetch(`${baseURL}/api/supplier/${supplierId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Supplier data received:', data);
                if (data && data.id) {
                    currentSupplierId = data.id;
                    
                    // Fill form fields with supplier data
                    fillSupplierFields(data);
                    
                    // Set fields as readonly since we have a supplier
                    setSupplierFieldsReadOnly(true);
                    
                    // Update edit supplier link
                    updateEditSupplierLink(data.id);
                } else {
                    console.warn('Invalid supplier data received');
                    clearSupplierFields();
                    setSupplierFieldsReadOnly(false);
                    updateEditSupplierLink(null);
                }
            })
            .catch(error => {
                console.error('Error fetching supplier data:', error);
                clearSupplierFields();
                setSupplierFieldsReadOnly(false);
                updateEditSupplierLink(null);
            });
    }
    
    /**
     * Fill supplier fields with data
     * @param {Object} data - Supplier data object
     */
    function fillSupplierFields(data) {
        console.log('Filling supplier fields with data');
        
        // Loop through all supplier fields
        supplierFields.forEach(field => {
            const element = document.getElementById(field);
            if (element) {
                // Set field value if exists in data, otherwise clear
                element.value = data[field] || '';
                console.log(`Field ${field} set to: ${element.value}`);
            } else {
                console.warn(`Field element ${field} not found`);
            }
        });

        // Special handling for select elements like country and bank_code
        const countryElement = document.getElementById('country');
        if (countryElement && countryElement.tagName === 'SELECT' && data.country) {
            // Find and select the option with the correct value
            const option = countryElement.querySelector(`option[value="${data.country}"]`);
            if (option) {
                option.selected = true;
                console.log(`Country select set to: ${data.country}`);
            }
        }

        const bankCodeElement = document.getElementById('bank_code');
        if (bankCodeElement && bankCodeElement.tagName === 'SELECT' && data.bank_code) {
            // Find and select the option with the correct value
            const option = bankCodeElement.querySelector(`option[value="${data.bank_code}"]`);
            const optionEmpty = bankCodeElement.querySelector(`option[value="${data.bank_code}"]`);
            if (option) {
                option.selected = true;
                console.log(`Bank code select set to: ${data.bank_code}`);
            } else {
                // If no option found, set to empty
                bankCodeElement.value = 0;
                optionEmpty.selected = true;
                console.log(`Bank code select set to 0`);
            }
        }
    }
    
    /**
     * Clear all supplier fields
     */
    function clearSupplierFields() {
        console.log('Clearing all supplier fields');
        
        supplierFields.forEach(field => {
            const element = document.getElementById(field);
            if (element && element.id === 'bank_code') {
                element.value = '0';
                console.log(`Field ${field} cleared`);
            }
            else if (element) {
                element.value = '';
                console.log(`Field ${field} cleared`);
            }
        });
    }
    
    /**
     * Set supplier fields readonly state and apply appropriate CSS classes
     * @param {boolean} isReadOnly - Whether fields should be readonly
     */
    function setSupplierFieldsReadOnly(isReadOnly) {
        console.log(`Setting supplier fields readonly: ${isReadOnly}`);
        
        // Get all supplier-field class elements
        const supplierFieldElements = document.querySelectorAll('.supplier-field');
        
        supplierFieldElements.forEach(element => {
            if (isReadOnly) {
                // Set readonly attribute
                element.setAttribute('readonly', true);
                
                // Add visual indicator classes
                element.classList.add('bg-gray-200', 'text-gray-500');
                console.log(`Set ${element.id || element.name} to readonly with visual indicators`);
            } else {
                // Remove readonly attribute
                element.removeAttribute('readonly');
                
                // Remove visual indicator classes
                element.classList.remove('bg-gray-200', 'text-gray-500');
                console.log(`Set ${element.id || element.name} to editable`);
            }
        });
        
        // Special handling for select elements that can't be readonly
        const selectElements = document.querySelectorAll('select.supplier-field');
        selectElements.forEach(element => {
            if (isReadOnly) {
                // For selects, we set disabled instead of readonly
                element.disabled = true;
                console.log(`Select ${element.id || element.name} disabled`);
            } else {
                element.disabled = false;
                console.log(`Select ${element.id || element.name} enabled`);
            }
        });
    }
    
    /**
     * Update edit supplier link based on supplier ID
     * @param {number|null} supplierId - Supplier ID or null if no supplier selected
     */
    function updateEditSupplierLink(supplierId) {
        const editLink = document.getElementById('edit-supplier-link');
        if (!editLink) {
            console.warn('Edit supplier link element not found');
            return;
        }
        
        if (supplierId) {
            // Enable link and set href to edit page
            const editUrl = `${baseURL}/supplier/${supplierId}/edit`;
            editLink.href = editUrl;
            
            // Remove disabled classes
            editLink.classList.remove('pointer-events-none', 'bg-gray-200', 'text-gray-500', 'opacity-50');
            
            console.log(`Edit supplier link updated to: ${editUrl}`);
        } else {
            // Disable link
            editLink.href = '#';
            
            // Add disabled classes
            editLink.classList.add('pointer-events-none', 'bg-gray-200', 'text-gray-500', 'opacity-50');
            
            console.log('Edit supplier link disabled');
        }
    }
    
    /**
     * Get current supplier ID
     * @returns {number|null} Current supplier ID or null if none selected
     */
    function getCurrentSupplierId() {
        return currentSupplierId;
    }
    
    /**
     * Force reload supplier data from the current selection
     * Useful when needing to manually refresh fields
     */
    function refreshCurrentSupplier() {
        const supplierSelect = document.getElementById('supplier_id');
        if (supplierSelect && supplierSelect.value) {
            console.log('Manually refreshing current supplier data');
            
            // Create and dispatch a change event
            const event = new Event('change');
            supplierSelect.dispatchEvent(event);
        }
    }
    
    // Public API
    return {
        init,
        getCurrentSupplierId,
        refreshCurrentSupplier,
        setEditMode: function(mode) {
            isEditMode = mode;
        },
        isInEditMode: function() {
            return isEditMode;
        }
    };
})();

// Export the module
export default SupplierFormData;
