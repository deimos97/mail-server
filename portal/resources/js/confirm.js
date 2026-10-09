/**
 * Confirmaciones con la estética de la web en lugar de confirm() del navegador.
 *
 * Usa <dialog> nativo con showModal(): el navegador ya hace lo importante para la accesibilidad
 * (foco atrapado dentro, Escape cierra, el resto de la página queda inerte, rol de diálogo modal).
 * Al cerrar, el foco vuelve a donde estaba.
 *
 * En un formulario: data-confirm="Pregunta" y, opcionales, data-confirm-body, data-confirm-button
 * y data-confirm-cancel. Sin JavaScript el formulario se envía sin preguntar (como antes con confirm()).
 */

let dialog;

function build() {
    dialog = document.createElement('dialog');
    dialog.setAttribute('aria-labelledby', 'ugl-confirm-title');
    dialog.setAttribute('aria-describedby', 'ugl-confirm-body');
    dialog.className =
        'm-auto w-[calc(100%-2rem)] max-w-sm rounded-3xl bg-white p-6 text-tinta shadow-2xl ring-1 ring-stone-200 backdrop:bg-tinta/50 backdrop:backdrop-blur-[2px]';
    dialog.innerHTML = `
        <form method="dialog">
            <h2 id="ugl-confirm-title" class="text-xl font-extrabold tracking-tight"></h2>
            <p id="ugl-confirm-body" class="mt-2 text-sm text-stone-600"></p>
            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                <button value="cancel" autofocus data-cancel
                        class="rounded-2xl px-5 py-2.5 font-semibold ring-1 ring-stone-300 hover:bg-stone-50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rojo"></button>
                <button value="ok" data-ok
                        class="rounded-2xl bg-rojo px-5 py-2.5 font-bold text-white hover:bg-rojo-oscuro focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rojo"></button>
            </div>
        </form>`;
    // Clic fuera de la caja (en el fondo) = cancelar
    dialog.addEventListener('click', (event) => {
        if (event.target === dialog) dialog.close('cancel');
    });
    document.body.append(dialog);
}

/** Abre la confirmación. Devuelve una promesa con true si se acepta. */
export function confirmDialog({ title, body = '', confirm = 'Sí, continuar', cancel = 'Cancelar' }) {
    if (!dialog) build();
    if (typeof dialog.showModal !== 'function') return Promise.resolve(window.confirm(title)); // navegadores muy antiguos

    dialog.querySelector('#ugl-confirm-title').textContent = title;
    const bodyEl = dialog.querySelector('#ugl-confirm-body');
    bodyEl.textContent = body;
    bodyEl.hidden = body === '';
    dialog.querySelector('[data-ok]').textContent = confirm;
    dialog.querySelector('[data-cancel]').textContent = cancel;
    dialog.returnValue = '';

    const opener = document.activeElement;
    return new Promise((resolve) => {
        dialog.addEventListener('close', () => {
            opener?.focus?.();
            resolve(dialog.returnValue === 'ok');
        }, { once: true });
        dialog.showModal();
    });
}

/** Formularios con data-confirm: preguntan antes de enviarse. */
export function registerConfirmForms() {
    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.dataset.confirm) return;
        if (form.dataset.confirmed === '1') {
            delete form.dataset.confirmed;
            return;
        }
        event.preventDefault();
        const submitter = event.submitter;
        const ok = await confirmDialog({
            title: form.dataset.confirm,
            body: form.dataset.confirmBody,
            confirm: form.dataset.confirmButton,
            cancel: form.dataset.confirmCancel,
        });
        if (!ok) return;
        form.dataset.confirmed = '1';
        form.requestSubmit(submitter && submitter.form === form ? submitter : undefined);
    });
}
