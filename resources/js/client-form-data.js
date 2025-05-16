/**
 * Client Form Data Module
 * This module handles client data population and field management in invoice creation form.
 * It loads default client data on page load and handles client selection changes.
 */
const ClientFormData = (function() {
    // Define client field IDs for data mapping
    const clientFields = [
        'client_name', 'client_email', 'client_phone', 'client_street', 
        'client_city', 'client_zip', 'client_country', 'client_ico', 'client_dic'
    ];
    
    // Base URL for API requests
    const baseURL = window.location.origin;
    
    // Keep track of current client ID
    let currentClientId = null;
    
    // Flag to determine if we're in edit mode
    let isEditMode = false;
    
    /**
     * Initialize the module
     */
    function init(options = {}) {
        console.log('Initializing ClientFormData module');
        
        // Set edit mode flag
        isEditMode = options.isEditMode || false;
        console.log(`Edit mode: ${isEditMode}`);

        // Load default supplier on page load only if not in edit mode
        if (!isEditMode) {
            loadDefaultClient();
        }
        
        // Set up event listener for client select change
        const clientSelect = document.getElementById('client_id');
        if (clientSelect) {
            var event = new Event('change');
            clientSelect.addEventListener('change', handleClientChange);
            clientSelect.dispatchEvent(event);

            console.log('Event listener for client select added');
        } else {
            console.warn('Client select element not found');
        }
    }
    
    /**
     * Load default client data on page load
     */
    function loadDefaultClient() {
        console.log('Loading default client data');
        
        fetch(`${baseURL}/api/client/default`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Default client data received:', data);
                if (data && data.id) {
                    // Update select with default client if it exists
                    const clientSelect = document.getElementById('client_id');
                    if (clientSelect) {
                        clientSelect.value = data.id;
                        currentClientId = data.id;
                        console.log(`Default client set to ID: ${data.id}`);
                    }
                    
                    // Fill form fields with client data
                    fillClientFields(data);
                    
                    // Set fields as readonly since we have a client
                    setClientFieldsReadOnly(true);
                    
                    // Update edit client link
                    updateEditClientLink(data.id);
                } else {
                    console.log('No default client found or data is invalid');
                    clearClientFields();
                    setClientFieldsReadOnly(false);
                    updateEditClientLink(null);
                }
            })
            .catch(error => {
                console.error('Error fetching default client:', error);
                clearClientFields();
                setClientFieldsReadOnly(false);
                updateEditClientLink(null);
            });
    }
    
    /**
     * Handle client select change event
     * @param {Event} event - Change event
     */
    function handleClientChange(event) {
        const clientId = event.target.value;
        console.log(`Client changed to ID: ${clientId}`);
        
        if (!clientId) {
            console.log('No client selected, clearing fields');
            clearClientFields();
            setClientFieldsReadOnly(false);
            updateEditClientLink(null);
            currentClientId = null;
            return;
        }
        
        // Fetch client data from API
        fetch(`${baseURL}/api/client/${clientId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Client data received:', data);
                if (data && data.id) {
                    currentClientId = data.id;
                    
                    // Fill form fields with client data
                    fillClientFields(data);
                    
                    // Set fields as readonly since we have a client
                    setClientFieldsReadOnly(true);
                    
                    // Update edit client link
                    updateEditClientLink(data.id);
                } else {
                    console.warn('Invalid client data received');
                    clearClientFields();
                    setClientFieldsReadOnly(false);
                    updateEditClientLink(null);
                }
            })
            .catch(error => {
                console.error('Error fetching client data:', error);
                clearClientFields();
                setClientFieldsReadOnly(false);
                updateEditClientLink(null);
            });
    }
    
    /**
     * Fill client fields with data
     * @param {Object} data - Client data object
     */
    function fillClientFields(data) {
        console.log('Filling client fields with data');
        
        // Loop through all client fields
        clientFields.forEach(field => {
            // Get field name without client_ prefix for data object
            const dataField = field.replace('client_', '');
            
            const element = document.getElementById(field);
            if (element) {
                if (element.tagName === 'SELECT') {
                    if (data[field]) {
                        // Client country select handling
                        const option = element.querySelector(`option[value="${data[field]}"]`);
                        if (option) {
                            element.value = data[field];
                            console.log(field + ` select set to: ${data[field]}`);
                        }
                        
                        // Update fallback field
                        const fallbackField = document.getElementById(field + '_fallback');
                        if (fallbackField) {
                            fallbackField.value = data[field];
                        }
                    } else {
                        // Generic select handling
                        if (data[field]) {
                            element.value = data[field];
                            console.log(`Select ${field} set to: ${data[field]}`);
                        }
                    }
                } else {
                    // Set field value if exists in data, otherwise clear
                    element.value = data[dataField] || '';
                    console.log(`Field ${field} set to: ${element.value}`);
                }
            } else {
                console.warn(`Field element ${field} not found`);
            }
        });

        // Special handling for select elements like client_country
        const countryElement = document.getElementById('client_country');
        if (countryElement && countryElement.tagName === 'SELECT' && data.country) {
            // Find and select the option with the correct value
            const option = countryElement.querySelector(`option[value="${data.country}"]`);
            if (option) {
                option.selected = true;
                console.log(`Client country select set to: ${data.country}`);
            }
        }
    }
    
    /**
     * Clear all client fields
     */
    function clearClientFields() {
        console.log('Clearing all client fields');
        
        clientFields.forEach(field => {
            const element = document.getElementById(field);
            if (element) {
                element.value = '';
                console.log(`Field ${field} cleared`);
            }
        });
    }
    
    /**
     * Set client fields readonly state and apply appropriate CSS classes
     * @param {boolean} isReadOnly - Whether fields should be readonly
     */
    function setClientFieldsReadOnly(isReadOnly) {
        console.log(`Setting client fields readonly: ${isReadOnly}`);
        
        // Get all client-field class elements
        const clientFieldElements = document.querySelectorAll('.client-field');
        
        clientFieldElements.forEach(element => {
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
        const selectElements = document.querySelectorAll('select.client-field');
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
     * Update edit client link based on client ID
     * @param {number|null} clientId - Client ID or null if no client selected
     */
    function updateEditClientLink(clientId) {
        const editLink = document.getElementById('edit-client-link');
        if (!editLink) {
            console.warn('Edit client link element not found');
            return;
        }
        
        if (clientId) {
            // Enable link and set href to edit page
            const editUrl = `${baseURL}/client/${clientId}/edit`;
            editLink.href = editUrl;
            
            // Remove disabled classes
            editLink.classList.remove('pointer-events-none', 'bg-gray-200', 'text-gray-500', 'opacity-50');
            
            console.log(`Edit client link updated to: ${editUrl}`);
        } else {
            // Disable link
            editLink.href = '#';
            
            // Add disabled classes
            editLink.classList.add('pointer-events-none', 'bg-gray-200', 'text-gray-500', 'opacity-50');
            
            console.log('Edit client link disabled');
        }
    }
    
    /**
     * Get current client ID
     * @returns {number|null} Current client ID or null if none selected
     */
    function getCurrentClientId() {
        return currentClientId;
    }
    
    /**
     * Force reload client data from the current selection
     * Useful when needing to manually refresh fields
     */
    function refreshCurrentClient() {
        const clientSelect = document.getElementById('client_id');
        if (clientSelect && clientSelect.value) {
            console.log('Manually refreshing current client data');
            
            // Create and dispatch a change event
            const event = new Event('change');
            clientSelect.dispatchEvent(event);
        }
    }
    
    // Public API
    return {
        init,
        getCurrentClientId,
        refreshCurrentClient,
        setEditMode: function(mode) {
            isEditMode = mode;
        },
        isInEditMode: function() {
            return isEditMode;
        }
    };
})();

// Export the module
export default ClientFormData;
