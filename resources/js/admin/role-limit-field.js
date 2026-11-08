// The number of a role limit is shown only for the limited mode.
document.querySelectorAll('[data-role-limit-mode]').forEach(select => {
    select.addEventListener('change', () => {
        select.nextElementSibling.hidden = select.value !== select.dataset.limited;
    });
});
