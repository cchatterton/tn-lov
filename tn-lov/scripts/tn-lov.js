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
});
