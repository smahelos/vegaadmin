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
    const url = `http://vegaadmin.local/api/client/${clientId}`;

    fetch(url, {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': token,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (callback && typeof callback === 'function') {
            callback(data);
        }
    })
    .catch(error => {
        console.error('Chyba při načítání dat klienta:', error);
    });
}

crud.field('client_id').onChange(function(field) {
    //alertIt('This is a custom alert message. - ' + field.value);
    // if (parseInt(field.value) === 1) {
        getClientData(field.value, function(clientData) {
            console.log('Data klienta:', clientData);
            crud.field('ico').setValue(clientData.ico);
            crud.field('dic').setValue(clientData.dic);
            crud.field('name').setValue(clientData.name);
            crud.field('street').setValue(clientData.street);
            crud.field('city').setValue(clientData.city);
            crud.field('zip').setValue(clientData.zip);
            crud.field('country').setValue(clientData.country);
            // Zde můžeš pracovat s daty klienta
        });
    // }
}).change();