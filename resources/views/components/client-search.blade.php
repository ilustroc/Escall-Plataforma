<div class="client-search" data-client-search data-endpoint="{{ route('clientes.suggestions') }}">
    <label class="sr-only" for="global-client-search">Buscar clientes</label>
    <svg class="client-search-icon" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6"/><path d="m20 20-4.35-4.35" /></svg>
    <input id="global-client-search" type="search" class="client-search-input" placeholder="Buscar por documento, código o titular..." autocomplete="off" maxlength="100" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="client-search-results" aria-describedby="client-search-status" data-client-search-input>
    <div id="client-search-status" class="sr-only" role="status" aria-live="polite" data-client-search-status></div>
    <div id="client-search-results" class="client-search-results" data-client-search-results role="listbox" hidden></div>
</div>
