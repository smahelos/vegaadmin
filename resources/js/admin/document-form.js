// Select2 owns the record search; the document keeps its own party and account copies.
const $ = window.jQuery;

function valuesOf(fields, prefix) {
    return Object.fromEntries([...fields].map(field => [field.name.slice(prefix.length + 1), $(field).val()]));
}

function fill(fields, prefix, values) {
    fields.each(function () {
        $(this).val(values[this.name.slice(prefix.length + 1)] ?? '').trigger('change.select2');
    });
}

function bindSelection(element, {read, write, lock, status}) {
    const storedId = String(element.data('stored-reference-id') || '');
    const snapshot = {values: read()};
    let previousId = String(element.val() || '');
    let generation = 0;

    async function update() {
        const id = String(element.val() || '');
        const requestGeneration = ++generation;
        if (previousId === storedId && storedId) snapshot.values = read();
        previousId = id;
        const copy = !!id && id !== storedId;
        lock(copy);
        if (!id) {
            status('manual');
            return;
        }
        if (id === storedId) {
            write(snapshot.values);
            status('correction');
            return;
        }

        status('loading');
        // Never leave the previous contact's data visible while a different one is loading.
        write({});
        const selected = element.select2('data')[0];
        if (selected?.profile || selected?.account) {
            write(selected.profile || selected.account);
            status('copy');
            return;
        }
        const params = {id};
        for (const dependency of element.data('dependencies') || []) {
            params[dependency] = element.closest('form').find(`[name="${dependency}"]`).val();
        }
        try {
            const response = await $.getJSON(element.data('source'), params);
            if (requestGeneration !== generation) return;
            const record = (response.data || response).find(record => String(record.id) === id);
            if (!record) throw new Error('The selected document source is unavailable');
            write(record.profile || record.account || {});
            status('copy');
        } catch {
            if (requestGeneration === generation) status('error');
        }
    }

    element.on('change.document', update);
    // A stored reference starts with the saved document copy, even if the source has changed or is archived.
    if (previousId && previousId !== storedId) update();
    else {
        lock(false);
        status(previousId ? 'correction' : 'manual');
    }
}

function initialize(form) {
    const contact = form.find('[data-counterparty-role]');
    const business = form.find('[data-document-business-role]').filter(function () { return !!this.dataset.documentBusinessRole; });
    const account = form.find('[data-document-account="true"]');
    if (!contact.length || !business.length || !account.length) return;

    const role = contact.data('counterparty-role');
    const partyFields = form.find(`[name^="${role}_"]`).not('[name="supplier_logo"]');
    const contactStatus = form.find('[data-document-contact-status]');
    partyFields.each(function () {
        $(this).data('document-required', $(this).closest('[bp-field-wrapper]').hasClass('required'));
    });
    bindSelection(contact, {
        read: () => valuesOf(partyFields, role),
        write: values => fill(partyFields, role, values),
        lock: copy => partyFields.prop('disabled', copy).each(function () {
            $(this).closest('[bp-field-wrapper]').toggleClass('required', !copy && $(this).data('document-required'));
        }),
        status: mode => contactStatus.text(contactStatus.data(mode)).toggleClass('text-danger', mode === 'error'),
    });

    const preview = form.find('[data-document-profile="business"]');
    bindSelection(business, {
        read: () => Object.fromEntries([...preview.find('[data-profile-key]')].map(item => [item.dataset.profileKey, item.textContent === '—' ? '' : item.textContent])),
        write: values => preview.find('[data-profile-key]').each(function () { $(this).text(values[this.dataset.profileKey] || '—'); }),
        lock: () => {},
        status: mode => { if (mode === 'manual') preview.find('[data-profile-key]').text('—'); },
    });

    const logo = form.find('[data-document-logo]');
    if (logo.length) {
        const source = logo.data('logo-source') === 'business' ? business : contact;
        const originalContact = String(contact.data('stored-reference-id') || '');
        const draw = url => {
            logo.find('img').attr('src', url || '').prop('hidden', !url);
            logo.find('[data-no-logo]').prop('hidden', !!url);
        };
        bindSelection(source, {
            read: () => ({logo_url: logo.find('img').attr('src') || ''}),
            write: values => draw(values.logo_url),
            lock: () => {
                const selected = !!contact.val();
                logo.find('[data-one-off-logo]').toggle(!selected).find('input').prop('disabled', selected);
            },
            status: mode => {
                if (mode === 'manual') {
                    const keepOneOff = !originalContact && source === contact;
                    draw(keepOneOff ? logo.data('stored-logo') : '');
                }
            },
        });
    }

    const accountFields = form.find('[name^="account_"]');
    const accountMode = form.find('[data-document-account-mode]');
    const oneOff = {values: valuesOf(accountFields, 'account')};
    const storedAccount = String(account.data('stored-reference-id') || '');
    let previousAccount = String(account.val() || '');
    bindSelection(account, {
        read: () => valuesOf(accountFields, 'account'),
        write: values => fill(accountFields, 'account', values),
        lock: () => {
            const selected = !!account.val();
            if (!previousAccount) oneOff.values = valuesOf(accountFields, 'account');
            if (!selected && previousAccount) fill(accountFields, 'account', oneOff.values);
            previousAccount = String(account.val() || '');
            accountFields.prop('disabled', selected || !!account.data('bank-transfer-method'));
            accountFields.closest('[bp-field-wrapper]').find('.help-block').toggle(!selected);
            accountMode.text(accountMode.data(selected ? 'selected' : 'one-off'));
        },
        status: mode => {
            if (mode === 'manual' && !accountMode.length) fill(accountFields, 'account', {});
        },
    });
    // A selected account's copy cannot become a one-off account just by clearing the selection.
    if (storedAccount) oneOff.values = {};

    const bankTransferMethod = account.data('bank-transfer-method');
    if (bankTransferMethod) {
        const method = form.find('[name="payment_method_id"]');
        const section = form.find('[data-document-section="account"]');
        function updateVisibility() {
            const required = String(method.val()) === String(bankTransferMethod);
            account.prop('required', required).closest('[bp-field-wrapper]').toggle(required).toggleClass('required', required);
            section.add(form.find('[data-document-account-field]')).toggle(required);
        }
        method.on('change.document', updateVisibility);
        updateVisibility();
    }
}

// Vite modules run before DOMContentLoaded; Backpack initializes its fields in jQuery's ready callback.
$(function () { $('form').each(function () { initialize($(this)); }); });
