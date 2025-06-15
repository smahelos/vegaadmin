/**
 * Získá data klienta podle ID pomocí AJAX
 * @param {number} clientId - ID klienta
 * @param {function} callback - Callback funkce, která bude zavolána s daty klienta
 */
function getClientData(clientId, callback) {
    if (!clientId) {
        console.error('Client ID je povinné');
        return;
    }

    // Získání CSRF tokenu z meta tagu
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Vytvoření URL pro Backpack API endpoint - použijeme API endpoint
    const url = `/api/admin/client/${clientId}`;

    fetch(url, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin' // Důležité: zajistí předání cookies včetně session ID
    })
    .then(response => {
        console.log('API response status:', response.status);
        
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
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Data klienta úspěšně načtena:', data);
        if (callback && typeof callback === 'function') {
            callback(data);
        }
    })
    .catch(error => {
        console.error('Chyba při načítání dat klienta:', error);
        // Zobrazit chybu uživateli jen pokud nejde o vypršení session
        if (!error.message.includes('přihlášení vypršelo')) {
            alert(`Chyba při načítání dat klienta: ${error.message}`);
        }
    });
}

/**
 * Získá data dodavatele podle ID pomocí AJAX
 * @param {number} supplierId - ID dodavatele
 * @param {function} callback - Callback funkce, která bude zavolána s daty dodavatele
 */
function getSupplierData(supplierId, callback) {
    if (!supplierId) {
        console.error('Supplier ID je povinné');
        return;
    }

    // Získání CSRF tokenu z meta tagu
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    // Vytvoření URL pro Backpack API endpoint - použijeme API endpoint
    const url = `/api/admin/supplier/${supplierId}`;

    console.log('getSupplierData', url);

    fetch(url, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin' // Důležité: zajistí předání cookies včetně session ID
    })
    .then(response => {
        console.log('API response status:', response.status);
        
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
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Data dodavatele úspěšně načtena:', data);
        if (callback && typeof callback === 'function') {
            callback(data);
        }
    })
    .catch(error => {
        console.error('Chyba při načítání dat dodavatele:', error);
        // Zobrazit chybu uživateli jen pokud nejde o vypršení session
        if (!error.message.includes('přihlášení vypršelo')) {
            alert(`Chyba při načítání dat dodavatele: ${error.message}`);
        }
    });
}

/**
 * Check if invoice form has an ID (alternative method)
 * @returns {boolean} true if invoice has ID, false otherwise
 */
function hasInvoiceVs() {
    const idField = crud.field('invoice_vs');
    return idField && idField.input && idField.input.value && idField.input.value !== '';
}

crud.field('client_id').onChange(function(field) {
    getClientData(field.value, function(clientData) {
        console.log('Data klienta:', clientData);
        crud.field('client_name').input.value = clientData.name;
        crud.field('client_email').input.value = clientData.email;
        crud.field('client_phone').input.value = clientData.phone;
        crud.field('client_street').input.value = clientData.street;
        crud.field('client_city').input.value = clientData.city;
        crud.field('client_zip').input.value = clientData.zip;
        crud.field('client_country').input.value = clientData.country;
        crud.field('client_ico').input.value = clientData.ico;
        crud.field('client_dic').input.value = clientData.dic;
    });
});

crud.field('supplier_id').onChange(function(field) {
    getSupplierData(field.value, function(supplierData) {
        console.log('Data dodavatele:', supplierData);
        crud.field('name').input.value = supplierData.name;
        crud.field('email').input.value = supplierData.email;
        crud.field('phone').input.value = supplierData.phone;
        crud.field('street').input.value = supplierData.street;
        crud.field('city').input.value = supplierData.city;
        crud.field('zip').input.value = supplierData.zip;
        crud.field('country').input.value = supplierData.country;
        crud.field('ico').input.value = supplierData.ico;
        crud.field('dic').input.value = supplierData.dic;
        crud.field('account_number').input.value = supplierData.account_number;
        crud.field('bank_code').input.value = supplierData.bank_code;
        crud.field('bank_name').input.value = supplierData.bank_name;
        crud.field('iban').input.value = supplierData.iban;
        crud.field('swift').input.value = supplierData.swift;
    });
});

if (hasInvoiceVs()) {
    crud.field('client_id').change();
    crud.field('supplier_id').change();
}
