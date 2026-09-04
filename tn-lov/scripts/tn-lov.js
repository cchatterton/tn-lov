document.addEventListener('DOMContentLoaded', () => {
    const migrationPanel = document.querySelector('.tn-lov-migration[data-migration-status]');
    const dismissMigration = migrationPanel?.querySelector('.tn-lov-migration__dismiss');

    dismissMigration?.addEventListener('click', async () => {
        if (!window.TN_LOV_ADMIN) {
            return;
        }

        dismissMigration.disabled = true;

        try {
            const body = new URLSearchParams({
                action: 'tn_lov_dismiss_migration',
                nonce: window.TN_LOV_ADMIN.dismissNonce,
                status: migrationPanel.dataset.migrationStatus,
            });
            const response = await fetch(window.TN_LOV_ADMIN.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body,
            });
            const result = await response.json();

            if (!response.ok || !result.success) {
                throw new Error('Migration status could not be dismissed.');
            }

            migrationPanel.remove();
        } catch (error) {
            dismissMigration.disabled = false;
        }
    });

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

});
