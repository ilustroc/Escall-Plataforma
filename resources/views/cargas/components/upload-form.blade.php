@props(['type', 'title', 'description', 'templateUrl', 'uploadUrl', 'initialImportId' => null])
<section class="integration-card" data-import-root @if($initialImportId) data-import-id="{{ $initialImportId }}" data-status-url="{{ route('cargas.importaciones.estado', $initialImportId) }}" @endif>
    <div class="integration-card-heading"><div><h2>{{ $title }}</h2><p>{{ $description }}</p></div><a class="btn-secondary" href="{{ $templateUrl }}">Descargar plantilla</a></div>
    <form method="post" action="{{ $uploadUrl }}" enctype="multipart/form-data" data-mass-import-form>
        @csrf
        <label class="import-dropzone" data-import-dropzone for="import-file">
            <input id="import-file" name="file" type="file" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" data-import-file required>
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 16V4m0 0L7 9m5-5 5 5M5 15v3a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-3" /></svg>
            <strong>Arrastra un CSV o XLSX aquí, o selecciónalo</strong>
            <span>Máximo 50 MB · hasta 500 000 filas · el procesamiento se realiza en segundo plano.</span>
        </label>
        <p class="import-file-name" data-import-file-name aria-live="polite">Aún no se seleccionó un archivo.</p>
        <div class="integration-form-actions"><p>El período FEC_PRO se detecta y valida desde el archivo.</p><button type="submit" class="btn-primary" data-import-submit>Validar y procesar</button></div>
    </form>
    <section class="import-progress" data-import-progress hidden aria-live="polite"><div class="import-progress-top"><strong data-import-state>Archivo recibido</strong><span data-import-percent>—</span></div><div class="import-progress-track"><span data-import-progress-bar></span></div><dl><div><dt>Filas</dt><dd data-import-rows>—</dd></div><div><dt>Válidas</dt><dd data-import-valid>—</dd></div><div><dt>Insertadas</dt><dd data-import-inserted>0</dd></div><div><dt>Actualizadas</dt><dd data-import-updated>0</dd></div><div><dt>Fallidas</dt><dd data-import-failed>0</dd></div><div><dt>Periodo</dt><dd data-import-period>—</dd></div></dl><p data-import-message></p><a class="btn-secondary" data-import-errors hidden>Descargar errores</a></section>
</section>
