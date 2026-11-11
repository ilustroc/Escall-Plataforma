const terminalStates = new Set(['completado', 'fallido']);

function formatBytes(bytes) {
    if (!Number.isFinite(bytes)) return '';
    return `${(bytes / 1024 / 1024).toFixed(bytes >= 1024 * 1024 ? 1 : 2)} MB`;
}

function render(root, data) {
    const progress = root.querySelector('[data-import-progress]');
    if (!progress) return;
    progress.hidden = false;
    const total = Number(data.total_filas || 0);
    const processed = Number(data.filas_procesadas || 0);
    const percent = total > 0 ? Math.min(100, Math.round((processed / total) * 100)) : null;
    const set = (selector, value) => { const node = root.querySelector(selector); if (node) node.textContent = value; };
    set('[data-import-state]', String(data.estado || 'pendiente').replaceAll('_', ' '));
    set('[data-import-percent]', percent === null ? '—' : `${percent}%`);
    set('[data-import-rows]', total ? `${processed.toLocaleString()} / ${total.toLocaleString()}` : processed.toLocaleString());
    set('[data-import-valid]', Number(data.filas_validas || 0).toLocaleString());
    set('[data-import-inserted]', Number(data.insertadas || 0).toLocaleString());
    set('[data-import-updated]', Number(data.actualizadas || 0).toLocaleString());
    set('[data-import-failed]', Number(data.fallidas || 0).toLocaleString());
    set('[data-import-period]', data.periodo || '—');
    set('[data-import-message]', data.mensaje || '');
    const bar = root.querySelector('[data-import-progress-bar]'); if (bar) bar.style.width = `${percent || 0}%`;
    const errors = root.querySelector('[data-import-errors]'); if (errors) { errors.hidden = !data.errores_url; if (data.errores_url) errors.href = data.errores_url; }
}

function poll(root, statusUrl) {
    let stopped = false;
    const check = async () => {
        try {
            const response = await fetch(statusUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            if (!response.ok) throw new Error('No se pudo consultar el estado de la importación.');
            const data = await response.json(); render(root, data);
            if (!terminalStates.has(data.estado)) window.setTimeout(check, 1500);
        } catch (error) { if (!stopped) { const message = root.querySelector('[data-import-message]'); if (message) message.textContent = error.message; window.setTimeout(check, 3000); } }
    };
    check();
    return () => { stopped = true; };
}

document.querySelectorAll('[data-import-root]').forEach((root) => {
    const statusUrl = root.dataset.statusUrl;
    if (statusUrl) poll(root, statusUrl);
    const form = root.querySelector('[data-mass-import-form]');
    const input = root.querySelector('[data-import-file]');
    const name = root.querySelector('[data-import-file-name]');
    const dropzone = root.querySelector('[data-import-dropzone]');
    const submit = root.querySelector('[data-import-submit]');
    const showFile = () => { const file = input?.files?.[0]; if (name) name.textContent = file ? `${file.name} · ${formatBytes(file.size)}` : 'Aún no se seleccionó un archivo.'; };
    input?.addEventListener('change', showFile);
    ['dragenter', 'dragover'].forEach((event) => dropzone?.addEventListener(event, (e) => { e.preventDefault(); dropzone.classList.add('is-dragging'); }));
    ['dragleave', 'drop'].forEach((event) => dropzone?.addEventListener(event, (e) => { e.preventDefault(); dropzone.classList.remove('is-dragging'); }));
    dropzone?.addEventListener('drop', (event) => { if (input && event.dataTransfer?.files?.length) { input.files = event.dataTransfer.files; showFile(); } });
    form?.addEventListener('submit', async (event) => {
        event.preventDefault(); if (!input?.files?.length) return;
        if (submit) { submit.disabled = true; submit.textContent = 'Enviando…'; }
        try {
            const response = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const data = await response.json(); if (!response.ok) throw new Error(data.message || data.errors?.file?.[0] || 'No se pudo recibir el archivo.');
            render(root, { estado: 'pendiente', etapa: 'archivo_recibido' }); poll(root, data.status_url);
        } catch (error) { const message = root.querySelector('[data-import-message]'); if (message) message.textContent = error.message; const progress = root.querySelector('[data-import-progress]'); if (progress) progress.hidden = false; }
        finally { if (submit) { submit.disabled = false; submit.textContent = 'Validar y procesar'; } }
    });
});
