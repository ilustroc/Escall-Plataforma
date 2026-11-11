import '../css/app.css';

const storageKey = 'escall.sidebar.collapsed';
const root = document.documentElement;

try {
    if (localStorage.getItem(storageKey) === 'true') root.classList.add('sidebar-collapsed');
} catch (_) {
    // The interface remains usable when browser storage is unavailable.
}

function setSidebarCollapsed(collapsed) {
    root.classList.toggle('sidebar-collapsed', collapsed);
    document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
        button.setAttribute('aria-expanded', String(!collapsed));
        button.setAttribute('aria-label', collapsed ? 'Expandir menú' : 'Contraer menú');
    });
    try { localStorage.setItem(storageKey, String(collapsed)); } catch (_) { /* no storage */ }
}

function setMobileMenu(open) {
    root.classList.toggle('mobile-sidebar-open', open);
    document.querySelectorAll('[data-mobile-menu-toggle]').forEach((button) => button.setAttribute('aria-expanded', String(open)));
    const overlay = document.querySelector('[data-mobile-overlay]');
    if (overlay) overlay.hidden = !open;
}

function initSidebar() {
    document.querySelectorAll('[data-sidebar-toggle]').forEach((button) => {
        button.addEventListener('click', () => setSidebarCollapsed(!root.classList.contains('sidebar-collapsed')));
    });
    document.querySelectorAll('[data-mobile-menu-toggle]').forEach((button) => {
        button.addEventListener('click', () => setMobileMenu(!root.classList.contains('mobile-sidebar-open')));
    });
    document.querySelector('[data-mobile-overlay]')?.addEventListener('click', () => setMobileMenu(false));
    document.querySelectorAll('[data-sidebar-link]').forEach((link) => link.addEventListener('click', () => setMobileMenu(false)));
}

function initNotifications() {
    const rootElement = document.querySelector('[data-notifications]');
    if (!rootElement) return;
    const button = rootElement.querySelector('[data-notifications-toggle]');
    const panel = rootElement.querySelector('[data-notifications-panel]');
    const close = () => { panel.hidden = true; button.setAttribute('aria-expanded', 'false'); };
    button.addEventListener('click', () => {
        panel.hidden = !panel.hidden;
        button.setAttribute('aria-expanded', String(!panel.hidden));
    });
    document.addEventListener('click', (event) => { if (!rootElement.contains(event.target)) close(); });
    document.addEventListener('keydown', (event) => { if (event.key === 'Escape') close(); });
}

function appendHighlightedText(element, value, term) {
    const source = String(value || '');
    const normalized = source.toLocaleLowerCase('es-PE');
    const query = term.toLocaleLowerCase('es-PE');
    let cursor = 0;
    let match = normalized.indexOf(query, cursor);
    while (match !== -1) {
        element.append(document.createTextNode(source.slice(cursor, match)));
        const mark = document.createElement('mark');
        mark.textContent = source.slice(match, match + query.length);
        element.append(mark);
        cursor = match + query.length;
        match = normalized.indexOf(query, cursor);
    }
    element.append(document.createTextNode(source.slice(cursor)));
}

function initClientSearch() {
    const search = document.querySelector('[data-client-search]');
    if (!search) return;
    const input = search.querySelector('[data-client-search-input]');
    const results = search.querySelector('[data-client-search-results]');
    const status = search.querySelector('[data-client-search-status]');
    let timer;
    let controller;
    let suggestions = [];
    let activeIndex = -1;

    const close = () => {
        results.hidden = true;
        input.setAttribute('aria-expanded', 'false');
        input.removeAttribute('aria-activedescendant');
        activeIndex = -1;
    };
    const select = (client) => { window.location.assign(`/clientes?dni=${encodeURIComponent(client.documento)}`); };
    const render = (items, term) => {
        results.replaceChildren();
        suggestions = items;
        activeIndex = -1;
        if (!items.length) {
            const empty = document.createElement('p');
            empty.className = 'client-search-empty';
            empty.textContent = 'No se encontraron clientes';
            results.append(empty);
            results.hidden = false;
            input.setAttribute('aria-expanded', 'true');
            status.textContent = empty.textContent;
            return;
        }
        items.forEach((client, index) => {
            const option = document.createElement('button');
            option.type = 'button';
            option.id = `client-search-option-${index}`;
            option.className = 'client-search-option';
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', 'false');
            const documentLine = document.createElement('span');
            documentLine.className = 'client-search-document';
            appendHighlightedText(documentLine, client.documento, term);
            const name = document.createElement('span');
            name.className = 'client-search-name';
            appendHighlightedText(name, client.nombre || 'Titular no disponible', term);
            const meta = document.createElement('span');
            meta.className = 'client-search-meta';
            meta.textContent = `Código: ${client.codigo}${client.cartera ? ` · ${client.cartera}` : ''}`;
            option.append(documentLine, name, meta);
            option.addEventListener('mousedown', (event) => { event.preventDefault(); select(client); });
            results.append(option);
        });
        results.hidden = false;
        input.setAttribute('aria-expanded', 'true');
        status.textContent = `${items.length} sugerencias disponibles`;
    };
    const setActive = (index) => {
        const options = [...results.querySelectorAll('[role="option"]')];
        if (!options.length) return;
        activeIndex = (index + options.length) % options.length;
        options.forEach((option, itemIndex) => option.setAttribute('aria-selected', String(itemIndex === activeIndex)));
        const active = options[activeIndex];
        input.setAttribute('aria-activedescendant', active.id);
        active.scrollIntoView({ block: 'nearest' });
    };
    const request = async () => {
        const term = input.value.trim();
        if (term.length < 2) { close(); status.textContent = term ? 'Escribe al menos 2 caracteres' : ''; return; }
        controller?.abort();
        controller = new AbortController();
        status.textContent = 'Buscando clientes';
        try {
            const response = await fetch(`${search.dataset.endpoint}?q=${encodeURIComponent(term)}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: controller.signal });
            if (!response.ok) throw new Error('Search request failed');
            const payload = await response.json();
            if (term !== input.value.trim()) return;
            render(Array.isArray(payload.data) ? payload.data : [], term);
        } catch (error) {
            if (error.name === 'AbortError') return;
            close();
            status.textContent = 'No fue posible realizar la búsqueda';
        }
    };
    input.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(request, 300); });
    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown' && !results.hidden) { event.preventDefault(); setActive(activeIndex + 1); }
        else if (event.key === 'ArrowUp' && !results.hidden) { event.preventDefault(); setActive(activeIndex - 1); }
        else if (event.key === 'Enter' && activeIndex >= 0) { event.preventDefault(); select(suggestions[activeIndex]); }
        else if (event.key === 'Escape') { close(); input.focus(); }
    });
    document.addEventListener('click', (event) => { if (!search.contains(event.target)) close(); });
}

initSidebar();
initNotifications();
initClientSearch();
requestAnimationFrame(() => root.classList.add('sidebar-ready'));
