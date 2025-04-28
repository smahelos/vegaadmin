/**
 * Inicializace country selectboxů
 */
function initCountrySelect() {
    const countrySelects = document.querySelectorAll('.country-select');
    
    if (countrySelects.length === 0) return;
    
    fetch('/api/countries')
        .then(response => response.json())
        .then(countries => {
            // Seřazení podle názvu
            const sortedCountries = Object.values(countries).sort((a, b) => a.name.localeCompare(b.name));
            
            // Projít všechny selecty s třídou country-select
            countrySelects.forEach(select => {
                const selectedCountry = select.getAttribute('data-selected');
                
                // Přidání možností do selectu
                sortedCountries.forEach(country => {
                    const option = document.createElement('option');
                    option.value = country.code;
                    option.textContent = `${country.flag} ${country.code} - ${country.name}`;
                    option.selected = country.code === selectedCountry;
                    select.appendChild(option);
                });
            });
        })
        .catch(error => {
            console.error('Chyba při načítání zemí:', error);
            
            // Fallback možnosti pro případ chyby API
            const fallbackCountries = [
                { code: 'CZ', name: 'Česká republika' },
                { code: 'SK', name: 'Slovensko' },
                { code: 'DE', name: 'Německo' },
                { code: 'AT', name: 'Rakousko' }
            ];
            
            countrySelects.forEach(select => {
                const selectedCountry = select.getAttribute('data-selected');
                
                fallbackCountries.forEach(country => {
                    const option = document.createElement('option');
                    option.value = country.code;
                    option.textContent = `${country.code} - ${country.name}`;
                    option.selected = country.code === selectedCountry;
                    select.appendChild(option);
                });
            });
        });
}

// Inicializace po načtení DOMu
document.addEventListener('DOMContentLoaded', function() {
    initCountrySelect();
});

// Export pro použití v ostatních částech aplikace
export { initCountrySelect };