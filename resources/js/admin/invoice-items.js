function setupItems(editor) {
    const form = editor.closest('form');
    const rows = editor.querySelector('[data-item-rows]');
    const hidden = editor.querySelector('[name="invoice-products"]');
    const owner = form.querySelector('[name="user_id"]');
    const currency = form.querySelector('[name="payment_currency"]');
    const amount = form.querySelector('[name="payment_amount"]');
    const status = editor.querySelector('[data-items-status]');
    const catalogStatus = editor.querySelector('[data-catalog-status]');
    const select = editor.querySelector('[data-catalog-select]');
    const search = editor.querySelector('[data-catalog-search]');
    const totals = editor.querySelector('[data-item-totals]');
    const addCatalog = editor.querySelector('[data-add-catalog]');
    let products = [];
    let catalogGeneration = 0;
    let previewGeneration = 0;
    let previewTimer;
    let catalogTimer;

    function values() {
        return [...rows.children].map(row => Object.fromEntries(
            [...row.querySelectorAll('[data-key]')].map(input => [input.dataset.key, input.value]),
        ));
    }

    async function preview(generation) {
        if (!rows.children.length) return;
        status.textContent = editor.dataset.loading;
        try {
            const response = await fetch(editor.dataset.previewUrl, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': form.querySelector('[name="_token"]').value,
                },
                body: JSON.stringify({user_id: owner.value, payment_currency: currency.value, 'invoice-products': values()}),
            });
            const result = await response.json();
            if (generation !== previewGeneration) return;
            if (!response.ok) {
                status.textContent = Object.values(result.errors || {}).flat().join(' ') || editor.dataset.error;
                return;
            }
            const formatter = new Intl.NumberFormat(editor.dataset.locale, {style: 'currency', currency: result.data.currency});
            for (const key of ['subtotal', 'tax', 'total']) {
                editor.querySelector(`[data-${key}]`).textContent = formatter.format(result.data[key]);
            }
            amount.value = result.data.total;
            totals.hidden = false;
            status.textContent = '';
        } catch {
            if (generation === previewGeneration) status.textContent = editor.dataset.error;
        }
    }

    function changed() {
        const generation = ++previewGeneration;
        clearTimeout(previewTimer);
        hidden.value = JSON.stringify(values());
        amount.readOnly = rows.children.length > 0;
        totals.hidden = true;
        status.textContent = '';
        if (rows.children.length) previewTimer = setTimeout(() => preview(generation), 200);
    }

    function addItem(item = {}) {
        const row = editor.querySelector('[data-item-template]').content.firstElementChild.cloneNode(true);
        const defaults = {quantity: '1', unit: 'pieces', price: '0', currency: currency.value, tax_rate: '0', ...item};
        for (const input of row.querySelectorAll('[data-key]')) {
            const value = String(defaults[input.dataset.key] ?? '');
            if (input.dataset.key === 'tax_rate') {
                const option = [...input.options].find(option => Number(option.value) === Number(value));
                if (option) input.value = option.value;
                else input.add(new Option(value + ' %', value, true, true));
            } else input.value = value;
        }
        rows.append(row);
        changed();
        return row;
    }

    async function loadCatalog() {
        const generation = ++catalogGeneration;
        products = [];
        select.replaceChildren();
        addCatalog.disabled = true;
        catalogStatus.textContent = '';
        if (!owner.value || !currency.value) return;
        catalogStatus.textContent = editor.dataset.loading;
        try {
            const query = new URLSearchParams({user_id: owner.value, payment_currency: currency.value, q: search.value});
            const response = await fetch(editor.dataset.catalogUrl + '?' + query, {headers: {'Accept': 'application/json'}});
            if (!response.ok) throw new Error('Catalog request failed');
            const result = await response.json();
            if (generation !== catalogGeneration) return;
            products = result.data;
            for (const [index, product] of products.entries()) {
                select.add(new Option(product.name + ' · ' + product.price + ' ' + product.currency, String(index)));
            }
            addCatalog.disabled = products.length === 0;
            catalogStatus.textContent = products.length ? '' : editor.dataset.empty;
        } catch {
            if (generation === catalogGeneration) catalogStatus.textContent = editor.dataset.error;
        }
    }

    rows.addEventListener('input', changed);
    rows.addEventListener('change', changed);
    rows.addEventListener('click', event => {
        if (event.target.closest('[data-remove-item]')) {
            event.target.closest('[data-item-row]').remove();
            changed();
        }
    });
    editor.querySelector('[data-add-custom]').addEventListener('click', () => addItem().querySelector('[data-key="name"]').focus());
    addCatalog.addEventListener('click', () => {
        const product = products[select.value];
        if (product) addItem(product);
    });
    search.addEventListener('input', () => {
        clearTimeout(catalogTimer);
        // Invalidate an in-flight search as soon as the text changes.
        ++catalogGeneration;
        addCatalog.disabled = true;
        catalogTimer = setTimeout(loadCatalog, 200);
    });
    const $ = window.jQuery;
    $(owner).on('change.invoiceItems', () => {
        rows.replaceChildren();
        changed();
        status.textContent = editor.dataset.ownerChanged;
        loadCatalog();
    });
    $(currency).on('change.invoiceItems', () => {
        changed();
        loadCatalog();
    });
    // Business changes keep the user's catalogue items and their saved prices.
    for (const item of JSON.parse(editor.dataset.items)) addItem(item);
    changed();
    loadCatalog();
    form.addEventListener('submit', () => { hidden.value = JSON.stringify(values()); });
}

function initialize() {
    document.querySelectorAll('[data-invoice-items]').forEach(setupItems);
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, {once: true});
else initialize();
