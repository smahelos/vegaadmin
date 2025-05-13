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
                const selectedCountry = select.getAttribute('data-selected');
                console.log('Processing select with selected country:', selectedCountry);
                
                // Add options to the select element
                sortedCountries.forEach(country => {
                    const option = document.createElement('option');
                    option.value = country.code;
                    option.textContent = `${country.flag} ${country.code} - ${country.name}`;
                    option.selected = country.code === selectedCountry;
                    select.appendChild(option);
                });
                
                console.log('Select populated with countries');
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
            });
        });
}

// Initialize country select on DOMContentLoaded
document.addEventListener('DOMContentLoaded', function() {
    initCountrySelect();
});

// Export for use in other modules
export { initCountrySelect };
