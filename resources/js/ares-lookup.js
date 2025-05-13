/**
 * ARES Lookup Integration
 * 
 * This script handles fetching data from ARES (Automated Register of Economic Subjects)
 * and populating form fields automatically
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize ARES lookup for forms that have the ICO field
    const icoFields = document.querySelectorAll('input[id="ico"], input[id="client_ico"]');
    
    icoFields.forEach(field => {
        addAresLookupButton(field);
    });

    function addAresLookupButton(icoField) {
        // Find the parent container that holds the input field
        let inputContainer = icoField.parentElement;
        
        // Find the label and hint elements (which are siblings of the input container)
        const label = inputContainer.previousElementSibling;
        // Hint is the next element after the input if it's a paragraph with text-xs text-gray-500 classes
        const hint = [...inputContainer.nextElementSibling?.classList || []].includes('text-xs') && 
                     [...inputContainer.nextElementSibling?.classList || []].includes('text-gray-500') ? 
                     inputContainer.nextElementSibling : null;
        
        // Create a wrapper div to contain everything
        const wrapperDiv = document.createElement('div');
        wrapperDiv.className = 'grid grid-cols-10 gap-2';
        
        // Create left column for label, input, and hint
        const leftColumn = document.createElement('div');
        leftColumn.className = 'col-span-8';
        
        // Create right column for button
        const rightColumn = document.createElement('div');
        rightColumn.className = 'col-span-1';
        
        // Insert wrapper before the input container
        inputContainer.parentNode.insertBefore(wrapperDiv, inputContainer);
        
        // Move label into left column if it exists
        if (label && label.tagName === 'LABEL') {
            leftColumn.appendChild(label.cloneNode(true));
            label.remove();
        }
        
        // Move input container into left column
        leftColumn.appendChild(inputContainer);
        
        // Move hint into left column if it exists
        if (hint) {
            leftColumn.appendChild(hint.cloneNode(true));
            hint.remove();
        }
        
        // Create Ares label
        const aresLabel = document.createElement('label');
        aresLabel.className = 'block text-base font-medium text-gray-500 mb-2 h-6';
        aresLabel.text = 'ARES';
        
        // Add Ares label to right column
        rightColumn.appendChild(aresLabel);
        
        // Check if the field is within client-fields or supplier-fields container
        let isClientField = false;
        let isSupplierField = false;
        
        // Check for client-fields or supplier-fields in parent elements
        // Start from the ICO field and traverse up to the root
        let parentElement = icoField;
        const maxDepth = 10; // Limit the depth of traversal to avoid infinite loops
        let depth = 0;
        
        while (parentElement && depth < maxDepth) {
            // Check parent element
            if (parentElement.closest) {
                const clientContainer = parentElement.closest('.client-fields');
                const supplierContainer = parentElement.closest('.supplier-fields');
                
                if (clientContainer) {
                    isClientField = true;
                    break;
                } else if (supplierContainer) {
                    isSupplierField = true;
                    break;
                }
            }
            
            // Check if the parent element itself has the classes
            if (parentElement.classList) {
                if (parentElement.classList.contains('client-fields') || parentElement.classList.contains('client-field')) {
                    isClientField = true;
                    break;
                } else if (parentElement.classList.contains('supplier-fields') || parentElement.classList.contains('supplier-field')) {
                    isSupplierField = true;
                    break;
                }
            }
            
            // Move up to parent
            parentElement = parentElement.parentElement;
            depth++;
        }
        
        // Alternative check: check based on the ID of the ICO field
        if (!isClientField && !isSupplierField) {
            if (icoField.id === 'client_ico') {
                isClientField = true;
            } else if (icoField.id === 'ico') {
                isSupplierField = true;
            }
        }
        
        // Create lookup button
        const lookupButton = document.createElement('button');
        lookupButton.type = 'button';
        
        // Set base classes for the button
        let buttonClasses = 'py-2 px-3 rounded-md text-white text-sm font-medium transition-colors ares-lookup-btn bg-blue-300 hover:bg-cyan-600 cursor-pointer';
        
        // Add specific classes based on the field location
        if (isClientField) {
            buttonClasses += ' client-field';
        } else if (isSupplierField) {
            buttonClasses += ' supplier-field';
        }
        
        lookupButton.className = buttonClasses;
        lookupButton.innerHTML = '<i class="fas fa-search"></i>';
        lookupButton.title = 'Vyhledat v ARES';
        lookupButton.setAttribute('aria-label', 'Vyhledat v ARES');
        
        // Add lookup button to right column
        rightColumn.appendChild(lookupButton);
        
        // Add columns to wrapper
        wrapperDiv.appendChild(leftColumn);
        wrapperDiv.appendChild(rightColumn);
        
        // Add click event
        lookupButton.addEventListener('click', () => performAresLookup(icoField));
    }

    function performAresLookup(icoField) {
        const icoValue = icoField.value.trim();
        
        // Validate ICO
        if (!icoValue || icoValue.length !== 8 || !/^\d+$/.test(icoValue)) {
            alert(getTranslation('errors.invalid_ico', 'Prosím, zadejte platné IČO (8 číslic).'));
            return;
        }
        
        // Find the button in the restructured DOM
        // Look for the closest wrapper first, then find the button inside it
        const wrapper = icoField.closest('.grid.grid-cols-10');
        if (!wrapper) {
            console.error('Could not find wrapper element for ARES lookup');
            return;
        }
        
        const button = wrapper.querySelector('.ares-lookup-btn');
        if (!button) {
            console.error('Could not find ARES lookup button');
            return;
        }
        
        // Show loading state
        const originalButtonContent = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        // Determine form type (supplier or client)
        const isClient = icoField.id === 'client_ico';
        
        // Make AJAX request to backend
        fetch(`/api/ares-lookup?ico=${icoValue}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Update form fields with retrieved data
                    populateFormFields(data.data, isClient);
                } else {
                    alert(data.message || getTranslation('errors.general_error', 'Nepodařilo se načíst data z ARES.'));
                }
            })
            .catch(error => {
                console.error('Error fetching data from ARES:', error);
                alert(getTranslation('errors.general_error', 'Došlo k chybě při komunikaci s ARES. Zkuste to prosím později.'));
            })
            .finally(() => {
                // Reset button state
                button.innerHTML = originalButtonContent;
                button.disabled = false;
            });
    }

    // Helper function to get translations or fallback to default text
    function getTranslation(key, defaultText) {
        // Check if window.translations exists and has the ares translations
        if (window.translations && window.translations.ares) {
            // Split the key by dots and navigate through the object
            const parts = key.split('.');
            let value = window.translations.ares;
            
            for (const part of parts) {
                if (value && value[part] !== undefined) {
                    value = value[part];
                } else {
                    return defaultText; // Key not found, return default
                }
            }
            
            return value;
        }
        
        return defaultText;
    }

    function populateFormFields(data, isClient) {
        const prefix = isClient ? 'client_' : '';
        
        // Map ARES fields to form fields
        const fieldMappings = {
            name: `${prefix}name`,
            street: `${prefix}street`,
            city: `${prefix}city`,
            zip: `${prefix}zip`,
            dic: `${prefix}dic`,
            country: `${prefix}country`
        };
        
        // Update each field if it exists
        Object.entries(fieldMappings).forEach(([aresField, formField]) => {
            if (data[aresField] && document.getElementById(formField)) {
                document.getElementById(formField).value = data[aresField];
            }
        });
    }
});
