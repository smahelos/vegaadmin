import './bootstrap';
import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';
import { initCountrySelect } from './country-select';

Alpine.plugin(focus);
window.Alpine = Alpine;
Alpine.start();

// Nahrazení jQuery AJAX nastavení pomocí využití fetch API
// Nová pomocná funkce pro AJAX požadavky
window.ajax = {
    setup(defaultOptions) {
        window.ajax.defaultOptions = defaultOptions;
    },
    request(url, options = {}) {
        const mergedOptions = { 
            ...window.ajax.defaultOptions,
            ...options,
            headers: {
                ...window.ajax.defaultOptions.headers,
                ...(options.headers || {})
            }
        };
        
        return fetch(url, mergedOptions);
    }
};

// Nastavení výchozích hodnot
window.ajax.setup({
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    credentials: 'same-origin'
});

// Alpine.js podpora
document.addEventListener('DOMContentLoaded', function() {
    // Inicializace tooltipů a popoverů (pokud by byly použity)
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    if (tooltipTriggerList.length > 0) {
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Responzivní menu toggle
    const mobileMenuButton = document.querySelector('[x-data]');
    if (mobileMenuButton) {
        mobileMenuButton.addEventListener('click', function() {
            document.dispatchEvent(new CustomEvent('toggle-mobile-menu'));
        });
    }

    // initializing country select
    initCountrySelect();
});

/**
 * Funkcionalita pro formuláře faktur
 */
const invoiceFormHandlers = {
    /**
     * Zpracování výběru klienta
     * @param {string} clientId - ID vybraného klienta
     */
    handleClientSelection(clientId) {
        console.log('handleClientSelection spuštěna s ID:', clientId);
        
        const clientNameContainer = document.getElementById('client_name_container');
        const clientNameInput = document.getElementById('client_name');
        
        if (!clientNameContainer || !clientNameInput) {
            console.error('Nenalezeny důležité elementy formuláře!');
            return;
        }
        
        if (clientId && clientId !== '') {
            console.log('Vybrán existující klient, skrývám pole client_name');
            
            // Skryjeme pole client_name při výběru existujícího klienta
            clientNameContainer.classList.add('hidden');
            
            // Odstraníme required atribut z client_name
            clientNameInput.removeAttribute('required');
            
            // Vymažeme hodnotu v poli client_name
            clientNameInput.value = '';
            
            // Získání CSRF tokenu pro API požadavek
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            console.log('Načítám data klienta přes API...');
            
            // Načtení dat klienta pomocí API s hlavičkami
            fetch(`/api/client/${clientId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin'
            })
            .then(response => {
                console.log('API odpověď status:', response.status);
                if (!response.ok) {
                    if (response.status === 401) {
                        return response.json().then(data => {
                            console.error('Autentizace selhala:', data);
                            if (data.redirect) {
                                // Místo automatického obnovení zobrazíme dialog
                                if (confirm('Vaše přihlášení vypršelo. Chcete se přihlásit znovu?')) {
                                    window.location.href = data.redirect;
                                }
                            }
                            throw new Error('Vaše přihlášení vypršelo.');
                        });
                    }
                    throw new Error(`Nepodařilo se načíst data klienta (Status: ${response.status})`);
                }
                return response.json();
            })
            .then(client => {
                console.log('Data klienta úspěšně načtena:', client);
                
                if (client) {
                    // Vyplňujeme formulář daty klienta
                    const fields = [
                        { id: 'client_email', value: client.email || '' },
                        { id: 'client_phone', value: client.phone || '' },
                        { id: 'client_street', value: client.street || '' },
                        { id: 'client_city', value: client.city || '' },
                        { id: 'client_zip', value: client.zip || '' },
                        { id: 'client_country', value: client.country || 'CZ' },
                        { id: 'client_ico', value: client.ico || '' },
                        { id: 'client_dic', value: client.dic || '' }
                    ];
                    
                    // Vyplňujeme jednotlivá pole a logujeme výsledky
                    fields.forEach(field => {
                        const element = document.getElementById(field.id);
                        if (element) {
                            element.value = field.value;
                            console.log(`${field.id} nastaveno na:`, field.value);
                        } else {
                            console.error(`Element ${field.id} nebyl nalezen!`);
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Chyba při načítání dat klienta:', error);
            });
        } else {
            console.log('Žádný klient nebyl vybrán, zobrazuji pole client_name');
            // Zobrazíme pole client_name, pokud není vybrán existující klient
            clientNameContainer.classList.remove('hidden');
        }
    },
    
    /**
     * Zpracování zadání názvu klienta
     * @param {HTMLElement} input - Element pro zadání názvu klienta
     */
    handleClientNameInput(input) {
        const clientIdSelect = document.getElementById('client_id');
        const value = input.value.trim();
        
        // Pokud pole client_name má alespoň 3 znaky, zrušíme výběr v client_id
        if (value.length >= 3) {
            clientIdSelect.value = '';
            // Nyní client_name aktivně vyplněno, odstraňte required z client_id
            const clientIdWrapper = document.querySelector('[data-field="client_id"]');
            if (clientIdWrapper) {
                const requiredSpan = clientIdWrapper.querySelector('.required-indicator');
                if (requiredSpan) requiredSpan.classList.add('hidden');
            }
        }
    },

    /**
     * Zpracování výběru dodavatele/vystavitele
     * @param {string} supplierId - ID vybraného dodavatele
     */
    handleSupplierSelection(supplierId) {
        console.log('handleSupplierSelection spuštěna s ID:', supplierId);
        
        const supplierNameContainer = document.getElementById('supplier_name_container');
        const supplierNameInput = document.getElementById('name');
        
        if (!supplierNameContainer || !supplierNameInput) {
            console.error('Nenalezeny důležité elementy formuláře!');
            return;
        }
        
        if (supplierId && supplierId !== '') {
            console.log('Vybrán existující dodavatel, skrývám pole name');
            
            // Skryjeme pole name při výběru existujícího klienta
            supplierNameContainer.classList.add('hidden');
            
            // Odstraníme required atribut z name
            supplierNameInput.removeAttribute('required');
            
            // Vymažeme hodnotu v poli name
            supplierNameInput.value = '';
            
            // Získání CSRF tokenu pro API požadavek
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            console.log('Načítám data dodavatele přes API...');
            
            // Načtení dat dodavatele pomocí API s hlavičkami
            fetch(`/api/supplier/${supplierId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                },
                credentials: 'same-origin' // Důležité pro přenos cookies včetně session
            })
            .then(response => {
                console.log('API odpověď status:', response.status);
                if (!response.ok) {
                    if (response.status === 404) {
                        throw new Error('Dodavatel nebyl nalezen');
                    } else {
                        throw new Error(`Nepodařilo se načíst data dodavatele (Status: ${response.status})`);
                    }
                }
                return response.json();
            })
            .then(supplier => {
                console.log('Data dodavatele úspěšně načtena:', supplier);
                
                if (supplier) {
                    // Vyplňujeme formulář daty klienta
                    const fields = [
                        { id: 'email', value: supplier.email || '' },
                        { id: 'phone', value: supplier.phone || '' },
                        { id: 'street', value: supplier.street || '' },
                        { id: 'city', value: supplier.city || '' },
                        { id: 'zip', value: supplier.zip || '' },
                        { id: 'country', value: supplier.country || 'CZ' },
                        { id: 'ico', value: supplier.ico || '' },
                        { id: 'dic', value: supplier.dic || '' },
                        { id: 'account_number', value: supplier.account_number || '' },
                        { id: 'bank_code', value: supplier.bank_code || '' },
                        { id: 'bank_name', value: supplier.bank_name || '' },
                        { id: 'iban', value: supplier.iban || '' },
                        { id: 'swift', value: supplier.swift || '' }
                    ];
                    
                    // Vyplňujeme jednotlivá pole a logujeme výsledky
                    fields.forEach(field => {
                        const element = document.getElementById(field.id);
                        if (element) {
                            element.value = field.value;
                            console.log(`${field.id} nastaveno na:`, field.value);
                        } else {
                            console.error(`Element ${field.id} nebyl nalezen!`);
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Chyba při načítání dat dodavatele:', error);
                alert('Chyba při načítání dat dodavatele: ' + error.message);
                
                // Resetujeme výběr dodavatele na prázdný a zobrazíme pole pro ručním zadání
                if (document.getElementById('supplier_id')) {
                    document.getElementById('supplier_id').value = '';
                }
                
                // Zobrazíme pole name
                supplierNameContainer.classList.remove('hidden');
            });
        } else {
            console.log('Žádný dodavatel nebyl vybrán, zobrazuji pole name');
            // Zobrazíme pole name, pokud není vybrán existující klient
            supplierNameContainer.classList.remove('hidden');
        }
    },
    
    /**
     * Zpracování zadání názvu dodavatele
     * @param {HTMLElement} input - Element pro zadání názvu dodavatele
     */
    handleSupplierNameInput(input) {
        const supplierIdSelect = document.getElementById('supplier_id');
        const value = input.value.trim();
        
        // Pokud pole name má alespoň 3 znaky, zrušíme výběr v supplier_id
        if (value.length >= 3) {
            supplierIdSelect.value = '';
            // Nyní name aktivně vyplněno, odstraňte required z supplier_id
            const supplierIdWrapper = document.querySelector('[data-field="supplier_id"]');
            if (supplierIdWrapper) {
                const requiredSpan = supplierIdWrapper.querySelector('.required-indicator');
                if (requiredSpan) requiredSpan.classList.add('hidden');
            }
        }
    },
    
    /**
     * Inicializace formuláře faktur
     */
    init() {
        console.log('Inicializace formuláře pro faktury');
        
        // Okamžitě zkontrolujeme stav DOM a spustíme inicializaci
        if (document.readyState === 'loading') {
            // DOM ještě není načten, počkáme na událost
            document.addEventListener('DOMContentLoaded', () => {
                console.log('DOM načten - spouštím inicializaci z listeneru');
                this.onDomLoaded();
            });
        } else {
            // DOM je již načten, můžeme spustit inicializaci přímo
            console.log('DOM již načten - spouštím inicializaci ihned');
            this.onDomLoaded();
        }
    },
    
    /**
     * Handler pro událost načtení DOM
     */
    onDomLoaded() {
        console.log('DOM plně načten - připojuji události pro faktury');
        
        // Určení, zda je uživatel přihlášen na základě přítomnosti select polí
        const isLoggedIn = document.getElementById('client_id') !== null && 
                           document.getElementById('supplier_id') !== null;

        // Získání reference na select prvek
        const clientIdSelect = document.getElementById('client_id');
        const clientNameInput = document.getElementById('client_name');
        
        // Získání reference na select prvek
        const supplierIdSelect = document.getElementById('supplier_id');
        const supplierNameInput = document.getElementById('name');
        
        if (isLoggedIn) {
            if (clientIdSelect) {
                console.log('client_id select nalezen, připojuji event listener');
                
                // Připojení event listeneru - použijeme jak change tak input události pro jistotu
                clientIdSelect.addEventListener('change', () => {
                    console.log('client_id změněn na:', clientIdSelect.value);
                    this.handleClientSelection(clientIdSelect.value);
                });
                
                // Zkontrolujeme, zda již má vybranou hodnotu (např. po načtení stránky s old daty)
                if (clientIdSelect.value) {
                    console.log('client_id má výchozí hodnotu:', clientIdSelect.value);
                    this.handleClientSelection(clientIdSelect.value);
                }
            } else {
                console.log('client_id select nebyl nalezen, ale měl by být (uživatel je přihlášen)');
            }
            
            if (supplierIdSelect) {
                console.log('supplier_id select nalezen, připojuji event listener');
                
                // Připojení event listeneru - použijeme jak change tak input události pro jistotu
                supplierIdSelect.addEventListener('change', () => {
                    console.log('supplier_id změněn na:', supplierIdSelect.value);
                    this.handleSupplierSelection(supplierIdSelect.value);
                });
                
                // Zkontrolujeme, zda již má vybranou hodnotu (např. po načtení stránky s old daty)
                if (supplierIdSelect.value) {
                    console.log('supplier_id má výchozí hodnotu:', supplierIdSelect.value);
                    this.handleSupplierSelection(supplierIdSelect.value);
                }
            } else {
                console.log('supplier_id select nebyl nalezen, ale měl by být (uživatel je přihlášen)');
            }
        } else {
            console.log('Nepřihlášený uživatel - přeskakuji inicializaci select polí');
        }
        
        if (clientNameInput) {
            // Při zadávání textu do client_name
            clientNameInput.addEventListener('input', () => {
                // Pro nepřihlášeného uživatele nepotřebujeme řešit synchronizaci s client_id
                if (isLoggedIn) {
                    this.handleClientNameInput(clientNameInput);
                }
            });
            
            // Inicializace na základě aktuální hodnoty (pouze pro přihlášené uživatele)
            if (isLoggedIn && clientNameInput.value.trim().length >= 3) {
                this.handleClientNameInput(clientNameInput);
            }
        }
        
        if (supplierNameInput) {
            // Při zadávání textu do name
            supplierNameInput.addEventListener('input', () => {
                // Pro nepřihlášeného uživatele nepotřebujeme řešit synchronizaci s supplier_id
                if (isLoggedIn) {
                    this.handleSupplierNameInput(supplierNameInput);
                }
            });
            
            // Inicializace na základě aktuální hodnoty (pouze pro přihlášené uživatele)
            if (isLoggedIn && supplierNameInput.value.trim().length >= 3) {
                this.handleSupplierNameInput(supplierNameInput);
            }
        }
        
        // Validace formuláře před odesláním
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', (event) => {
                const clientName = clientNameInput?.value.trim() || '';
                const supplierName = supplierNameInput?.value.trim() || '';
                
                // Pro přihlášené uživatele kontrolujeme buď ID nebo jméno
                if (isLoggedIn) {
                    const clientId = clientIdSelect?.value || '';
                    const supplierId = supplierIdSelect?.value || '';
                    
                    if (!clientId && clientName.length < 3) {
                        event.preventDefault();
                        alert('{{ __("invoices.validation.client_required") }}');
                        if (clientNameInput) clientNameInput.focus();
                    }
                    
                    if (!supplierId && supplierName.length < 3) {
                        event.preventDefault();
                        alert('{{ __("invoices.validation.supplier_required") }}');
                        if (supplierNameInput) supplierNameInput.focus();
                    }
                } 
                // Pro nepřihlášené uživatele kontrolujeme jen jména
                else {
                    if (clientName.length < 3) {
                        event.preventDefault();
                        alert('{{ __("invoices.validation.client_name_required") }}');
                        if (clientNameInput) clientNameInput.focus();
                    }
                    
                    if (supplierName.length < 3) {
                        event.preventDefault();
                        alert('{{ __("invoices.validation.supplier_name_required") }}');
                        if (supplierNameInput) supplierNameInput.focus();
                    }
                }
            });
        }
    }
};

// Inicializace funkcionality faktur - UPRAVENO pro lepší debugování
document.addEventListener('DOMContentLoaded', function() {
    console.log('Globální DOMContentLoaded event aktivován');
    
    // Detekce, zda jsme na stránce faktury
    const isInvoicePage = document.querySelector('form[action*="invoice"]');
    if (isInvoicePage) {
        console.log('Detekována stránka faktury, inicializuji formulář');
        // Použití setTimeout pro zajištění, že náš kód se spustí po Alpine.js
        setTimeout(() => {
            invoiceFormHandlers.init();
        }, 0);
    } else {
        console.log('Není stránka faktury, přeskakuji inicializaci formuláře');
    }
});

// Alternativní způsob inicializace pro debug
window.addEventListener('load', function() {
    console.log('Window load event aktivován');
    
    // Kontrola, zda formulář existuje ale nebyl inicializován
    const isInvoicePage = document.querySelector('form[action*="invoice"]');
    const debugElement = document.getElementById('invoice_form_debug');
    if (isInvoicePage && !debugElement) {
        console.log('Záložní inicializace formuláře při window.load');
        // Vytvoření debugovacího elementu pro kontrolu
        const debug = document.createElement('div');
        debug.id = 'invoice_form_debug';
        debug.style.display = 'none';
        document.body.appendChild(debug);
        
        invoiceFormHandlers.init();
    }
});


// Pro fetch API používejte vždy credentials: 'same-origin'
async function fetchWithSession(url, options = {}) {
    const defaultOptions = {
        credentials: 'same-origin',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    const mergedOptions = { 
        ...defaultOptions,
        ...options,
        headers: {
            ...defaultOptions.headers,
            ...(options.headers || {})
        }
    };
    
    const response = await fetch(url, mergedOptions);
    
    // Kontrola odpovědi pro detekci problémů s autentizací
    if (response.status === 401) {
        const data = await response.json();
        console.error('Autentizace selhala:', data);
        
        // Zobrazit dialog s možností přihlášení
        if (confirm('Vaše přihlášení vypršelo. Chcete se přihlásit znovu?')) {
            window.location.href = data.redirect || '/login';
        }
        throw new Error('Vaše přihlášení vypršelo.');
    }
    
    if (!response.ok) {
        throw new Error(`HTTP error! Status: ${response.status}`);
    }
    
    return response.json();
}

// Exportovat pro použití v modulech
window.fetchWithSession = fetchWithSession;
