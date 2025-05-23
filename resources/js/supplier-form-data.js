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
     * Check if URL has supplier_id parameter and return it
     * @returns {string|null} - supplier_id from URL or null if not present
     */
    function getSupplierIdFromUrl() {
        const urlParams = new URLSearchParams(window.location.search);
        const supplierId = urlParams.get('supplier_id');
        return supplierId;
    }
    
    /**
     * Initialize the module
     */
    function init(options = {}) {
        console.log('Initializing SupplierFormData module');
        
        // Set edit mode flag
        isEditMode = options.isEditMode || false;
        console.log(`Edit mode: ${isEditMode}`);

        // Check if supplier_id is in URL
        const urlSupplierId = getSupplierIdFromUrl();
        
        if (urlSupplierId) {
            console.log(`Found supplier_id in URL: ${urlSupplierId}`);
            // Load supplier from URL parameter instead of default
            loadSupplierById(urlSupplierId);
        } else if (!isEditMode) {
            // Only load default if no URL parameter and not in edit mode
            loadDefaultSupplier();
        }
        
        // Set up event listener for supplier select change
        const supplierSelect = document.getElementById('supplier_id');
        if (supplierSelect) {
            // Set the supplier select value if URL parameter exists
            if (urlSupplierId) {
                supplierSelect.value = urlSupplierId;
            }
            
            var event = new Event('change');
            supplierSelect.addEventListener('change', handleSupplierChange);
            supplierSelect.dispatchEvent(event);
            console.log('Event listener for supplier select added');
        } else {
            console.warn('Supplier select element not found');
        }
    }

    /**
     * Load supplier data by ID
     * @param {string} supplierId - ID of supplier to load
     */
    function loadSupplierById(supplierId) {
        console.log(`Loading supplier data for ID: ${supplierId}`);
        
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
                    // Update select with supplier if it exists
                    const supplierSelect = document.getElementById('supplier_id');
                    if (supplierSelect) {
                        supplierSelect.value = data.id;
                        currentSupplierId = data.id;
                        console.log(`Supplier set to ID: ${data.id}`);
                    }
                    
                    // Fill form fields with supplier data
                    fillSupplierFields(data);
                    
                    // Set fields as readonly since we have a supplier
                    setSupplierFieldsReadOnly(true);
                    
                    // Update edit supplier link
                    updateEditSupplierLink(data.id);
                } else {
                    console.log('No supplier found or data is invalid');
                    clearSupplierFields();
                    setSupplierFieldsReadOnly(false);
                    updateEditSupplierLink(null);
                }
            })
            .catch(error => {
                console.error('Error fetching supplier:', error);
                clearSupplierFields();
                setSupplierFieldsReadOnly(false);
                updateEditSupplierLink(null);
            });
    }
    
    /**
     * Load default supplier data on page load
     */
    function loadDefaultSupplier() {
        console.log('Loading default supplier data');
        
        fetch(`${baseURL}/api/supplier/default`)
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
                // Handle select elements differently
                if (element.tagName === 'SELECT') {
                    if (data[field]) {
                        console.log(`Setting ` + field + ` select to: ${data[field]}`);
                        
                        // Try to find the option with value
                        const option = Array.from(element.options).find(opt => opt.value === data[field]);
                        
                        if (option) {
                            // Use vanilla JS property + trigger change event
                            element.value = data[field];
                            
                            // Also trigger change event to ensure form state is updated
                            const event = new Event('change', { bubbles: true });
                            element.dispatchEvent(event);

                            console.log(`Select ${field} set to: ${data[field]}`);
                        } else {
                            // If there's no matching option, add one
                            const newOption = document.createElement('option');
                            newOption.value = data[field];
                            newOption.text = data[field] + ' - ' + (data.bank_name || '');
                            element.add(newOption);
                            
                            // Set selected property on the new option
                            newOption.selected = true;
                            
                            // Also update value property and trigger change
                            element.value = data[field];
                            const event = new Event('change', { bubbles: true });
                            element.dispatchEvent(event);
                            
                            console.log(`Added and selected new option for bank code: ${data[field]}`);
                        }
                        
                        // Update fallback field
                        const fallbackField = document.getElementById(field + '_fallback');
                        if (fallbackField) {
                            fallbackField.value = data[field];
                        }

                    } else {
                        // Generic select handling
                        if (data[field]) {
                            // Try to find the option and set it
                            const option = Array.from(element.options).find(opt => opt.value === data[field]);
                            if (option) {
                                option.selected = true;
                            }
                            
                            // Also set the value property
                            element.value = data[field];
                            
                            // Trigger change event
                            const event = new Event('change', { bubbles: true });
                            element.dispatchEvent(event);
                            
                            console.log(`Select ${field} set to: ${data[field]}`);
                        }
                    }
                } else {
                    // Normal input fields
                    element.value = data[field] || '';
                    console.log(`Field ${field} set to: ${element.value}`);
                }
            } else {
                console.warn(`Field element ${field} not found`);
            }
        });
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
