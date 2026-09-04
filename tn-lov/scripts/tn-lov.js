document.addEventListener('DOMContentLoaded', () => {
    const rows = document.querySelector('#tn-lov-rows');
    const addButton = document.querySelector('#tn-lov-add-row');
    const template = document.querySelector('#tn-lov-row-template');
    const nextIndex = document.querySelector('#tn-lov-next-index');

    if (!rows || !addButton || !template || !nextIndex) {
        return;
    }

    addButton.addEventListener('click', () => {
        const index = Number.parseInt(nextIndex.value, 10) || 0;
        rows.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(index)));
        nextIndex.value = String(index + 1);

        const addedRow = rows.lastElementChild;
        addedRow?.querySelector('input')?.focus();
    });

    rows.addEventListener('click', (event) => {
        const removeButton = event.target.closest('.tn-lov-remove-row');
        if (!removeButton) {
            return;
        }

        removeButton.closest('tr')?.remove();
    });

    document.querySelectorAll('.tn-lov-import[data-confirm-import]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirmImport)) {
                event.preventDefault();
            }
        });
    });

    const lookupInput = document.querySelector('#tn-lov-help-input');
    const lookupButton = document.querySelector('#tn-lov-help-get');
    const lookupResult = document.querySelector('#tn-lov-help-result');

    if (!lookupInput || !lookupButton || !lookupResult || !window.TN_LOV_ADMIN) {
        return;
    }

    const runLookup = async () => {
        const key = lookupInput.value.trim();
        lookupResult.classList.remove('is-success', 'is-error');

        if (!key) {
            lookupResult.textContent = window.TN_LOV_ADMIN.emptyKey;
            lookupResult.classList.add('is-error');
            lookupInput.focus();
            return;
        }

        lookupButton.disabled = true;
        lookupResult.textContent = window.TN_LOV_ADMIN.lookingUp;

        try {
            const body = new URLSearchParams({
                action: 'tn_lov_get_value',
                nonce: window.TN_LOV_ADMIN.lookupNonce,
                key,
            });
            const response = await fetch(window.TN_LOV_ADMIN.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body,
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error(result.data?.message || window.TN_LOV_ADMIN.requestFail);
            }

            lookupResult.textContent = String(result.data.value);
            lookupResult.classList.add('is-success');
        } catch (error) {
            lookupResult.textContent = error.message || window.TN_LOV_ADMIN.requestFail;
            lookupResult.classList.add('is-error');
        } finally {
            lookupButton.disabled = false;
        }
    };

    lookupButton.addEventListener('click', runLookup);
    lookupInput.addEventListener('keydown', (event) => {
        if ('Enter' === event.key) {
            event.preventDefault();
            runLookup();
        }
    });
});
