/**
 * Dynamické načítání měn pro select boxy
 */
document.addEventListener('DOMContentLoaded', function() {
    // Najdi všechny select boxy s třídou currency-select
    const currencySelects = document.querySelectorAll('.currency-select');
    
    if (currencySelects.length === 0) {
        return;
    }
    
    // Přidání možnosti "Načíst více měn"
    currencySelects.forEach(select => {
        const selectedValue = select.value;
        const loadMoreOption = document.createElement('option');
        loadMoreOption.value = "LOAD_MORE";
        loadMoreOption.textContent = "Načíst více měn...";
        select.appendChild(loadMoreOption);
        
        // Nastavíme zpět vybranou hodnotu
        select.value = selectedValue;
        
        // Přidání event listeneru pro načtení všech měn
        select.addEventListener('change', function(e) {
            if (e.target.value === 'LOAD_MORE') {
                loadAllCurrencies(e.target);
            }
        });
    });
    
    /**
     * Načte všechny dostupné měny z API
     */
    function loadAllCurrencies(selectElement) {
        const selectedValue = selectElement.getAttribute('data-previous-value') || 'CZK';
        selectElement.disabled = true;
        
        // Uložení předchozí hodnoty
        const loadingOption = document.createElement('option');
        loadingOption.textContent = "Načítání měn...";
        loadingOption.selected = true;
        
        // Vyprázdníme select a přidáme načítací zprávu
        selectElement.innerHTML = '';
        selectElement.appendChild(loadingOption);
        
        // Načtení všech měn z API
        fetch('/api/currencies/all')
            .then(response => response.json())
            .then(currencies => {
                // Vyprázdnění select boxu
                selectElement.innerHTML = '';
                
                // Přidání možností do selectu
                Object.entries(currencies).forEach(([code, name]) => {
                    const option = document.createElement('option');
                    option.value = code;
                    option.textContent = `${code}`;
                    option.selected = code === selectedValue;
                    selectElement.appendChild(option);
                });
                
                selectElement.disabled = false;
            })
            .catch(error => {
                console.error('Chyba při načítání měn:', error);
                selectElement.innerHTML = '';
                
                // Fallback možnosti pro případ chyby API
                const fallbackCurrencies = {
                    'CZK': 'CZK',
                    'EUR': 'EUR',
                    'USD': 'USD',
                    'GBP': 'GBP'
                };
                
                Object.entries(fallbackCurrencies).forEach(([code, name]) => {
                    const option = document.createElement('option');
                    option.value = code;
                    option.textContent = code;
                    option.selected = code === selectedValue;
                    selectElement.appendChild(option);
                });
                
                selectElement.disabled = false;
            });
    }
});