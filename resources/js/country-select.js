/**
 * Country select initialization
 */
function initCountrySelect() {
    const countrySelects = document.querySelectorAll('.country-select');
    
    if (countrySelects.length === 0) return;
    
    console.log('Initializing country select boxes');
    
    fetch('/api/countries')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            console.log('Got API response');
            return response.json();
        })
        .then(countries => {
            console.log('Countries data received:', countries);
            
            // Check if we got any countries
            if (!countries || Object.keys(countries).length === 0) {
                console.error('No countries data received from API');
                throw new Error('No countries received');
            }
            
            // Organize countries by name into an array for sorting
            const sortedCountries = Object.values(countries).sort((a, b) => a.name.localeCompare(b.name));

            console.log('Sorted countries:', sortedCountries);
            
            // List all country selects with class 'country-select'
            countrySelects.forEach(select => {
                const selectedValue = select.getAttribute('data-selected') || select.value;
                console.log('Processing select with ID:', select.id, 'with selected value:', selectedValue);
                
                // Empty existing options first to avoid duplicates
                select.innerHTML = '';
                
                // Add placeholder option
                const placeholderOption = document.createElement('option');
                placeholderOption.value = '';
                placeholderOption.text = select.getAttribute('placeholder') || 'Select country';
                select.appendChild(placeholderOption);
                
                // Add options to the select element
                sortedCountries.forEach(country => {
                    const option = document.createElement('option');
                    option.value = country.code;
                    option.textContent = `${country.flag} ${country.code} - ${country.name}`;
                    
                    // Set selected state if this is the selected country
                    if (country.code === selectedValue) {
                        option.selected = true;
                    }
                    
                    select.appendChild(option);
                });
                
                // Set value property to ensure form submission works
                if (selectedValue) {
                    select.value = selectedValue;
                    
                    // Update fallback fields if they exist
                    if (select.id === 'country') {
                        const fallbackField = document.getElementById('country_fallback');
                        if (fallbackField) {
                            fallbackField.value = selectedValue;
                        }
                    } else if (select.id === 'client_country') {
                        const fallbackField = document.getElementById('client_country_fallback');
                        if (fallbackField) {
                            fallbackField.value = selectedValue;
                        }
                    }
                }
                
                // Trigger change event to ensure any listeners are notified
                const event = new Event('change', { bubbles: true });
                select.dispatchEvent(event);
                
                console.log('Select populated with countries, current value:', select.value);
                
                // Add change event listener to make sure select value is properly set
                select.addEventListener('change', function(e) {
                    console.log(`Country ${select.id} changed to:`, select.value);
                    const value = e.target.value;
                    
                    if (select.id === 'country') {
                        const fallbackField = document.getElementById('country_fallback');
                        if (fallbackField) {
                            fallbackField.value = value;
                        }
                    } else if (select.id === 'client_country') {
                        const fallbackField = document.getElementById('client_country_fallback');
                        if (fallbackField) {
                            fallbackField.value = value;
                        }
                    }
                });
            });
        })
        .catch(error => {
            console.error('Error loading countries:', error);
            
            // Fallback options if API fails
            const fallbackCountries = [
                { code: 'CZ', name: 'Česká republika', flag: '🇨🇿' },
                { code: 'SK', name: 'Slovensko', flag: '🇸🇰' },
                { code: 'DE', name: 'Německo', flag: '🇩🇪' },
                { code: 'AT', name: 'Rakousko', flag: '🇦🇹' },
                { code: 'PL', name: 'Polsko', flag: '🇵🇱' },
                { code: 'GB', name: 'Spojené království', flag: '🇬🇧' },
                { code: 'US', name: 'Spojené státy', flag: '🇺🇸' }
            ];
            
            countrySelects.forEach(select => {
                const selectedCountry = select.getAttribute('data-selected');
                
                fallbackCountries.forEach(country => {
                    const option = document.createElement('option');
                    option.value = country.code;
                    option.textContent = `${country.flag || ''} ${country.code} - ${country.name}`;
                    option.selected = country.code === selectedCountry;
                    select.appendChild(option);
                });
                
                // Set fallback value and update fallback field (same as above)
                if (selectedCountry) {
                    select.value = selectedCountry;
                    
                    if (select.id === 'country') {
                        const fallbackField = document.getElementById('country_fallback');
                        if (fallbackField) {
                            fallbackField.value = selectedCountry;
                        }
                    } else if (select.id === 'client_country') {
                        const fallbackField = document.getElementById('client_country_fallback');
                        if (fallbackField) {
                            fallbackField.value = selectedCountry;
                        }
                    }
                }
            });
        });
}

// Initialize country select on DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    initCountrySelect();
});

// Export for use in other modules
export { initCountrySelect };
