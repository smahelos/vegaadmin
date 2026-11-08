// Select2 reports a change through jQuery only. A form without an owner input keeps the record's stored owner.
const $ = window.jQuery;

function setupUsage(box) {
    const owner = box.closest('form').querySelector('[name="user_id"]');
    let generation = 0;

    async function load() {
        const current = ++generation;
        // Cleared first, so a failed request never leaves the previous owner's usage on screen
        box.replaceChildren();
        const user = owner?.value || box.dataset.owner;
        if (!user) return;
        const url = new URL(box.dataset.url);
        url.searchParams.set('user_id', user);
        // Marked as AJAX, so an expired session answers 401 instead of a redirect to the login page
        const response = await fetch(url, {headers: {'X-Requested-With': 'XMLHttpRequest'}});
        const html = response.ok ? await response.text() : '';
        if (current === generation) box.innerHTML = html;
    }

    $(owner).on('change.entityUsage', load);
    load();
}

$(function () { $('[data-entity-usage]').each(function () { setupUsage(this); }); });
