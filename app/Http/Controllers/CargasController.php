<?php

namespace App\Http\Controllers;

use App\Http\Requests\MassImportRequest;
use App\Http\Requests\UploadRequest;
use App\Jobs\ProcessMassImport;
use App\Models\Importacion;
use App\Services\{DocumentService, ImportService};
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CargasController extends Controller
{
    private const TYPES = ['asignacion' => 'Asignación', 'pagos' => 'Pagos'];

    public function index(Request $request) { return view('cargas.index', ['imports' => $this->history($request)]); }
    public function asignacion(Request $request) { return view('cargas.asignacion', ['imports' => $this->history($request, 'asignacion')]); }
    public function pagos(Request $request) { return view('cargas.pagos', ['imports' => $this->history($request, 'pagos')]); }
    public function pending(string $kind) { abort_unless(in_array($kind, ['ccd', 'data'], true), 404); return view('cargas.pending', ['kind' => strtoupper($kind)]); }

    public function template(Request $request, ImportService $imports, DocumentService $documents)
    {
        $type = match ((string) $request->query('tipo', 'asignacion')) {
            'asignacion', 'data' => 'data',
            'pagos', 'seguimientos', 'evolutivo' => (string) $request->query('tipo'),
            default => abort(422),
        };
        return $documents->download($imports->template($type));
    }

    public function storeImport(MassImportRequest $request, string $type)
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);
        $file = $request->file('file'); $extension = strtolower($file->getClientOriginalExtension());
        $original = Str::limit(preg_replace('/[^A-Za-z0-9._ -]+/', '_', basename($file->getClientOriginalName())) ?: "importacion.{$extension}", 180, '');
        $uuid = (string) Str::uuid();
        $path = $file->storeAs("imports/{$uuid}", "source.{$extension}", 'local');
        abort_unless($path, 422, 'No se pudo almacenar el archivo temporal.');
        $import = Importacion::create(['uuid' => $uuid, 'usuario_id' => $request->user()->id, 'tipo' => $type, 'nombre_archivo' => $original, 'archivo_path' => $path, 'tamano_bytes' => $file->getSize(), 'estado' => 'pendiente', 'etapa' => 'archivo_recibido']);
        ProcessMassImport::dispatch($import->id)->onQueue('imports');
        if ($request->expectsJson()) return response()->json(['success' => true, 'id' => $import->id, 'status_url' => route('cargas.importaciones.estado', $import)]);
        return redirect()->route("cargas.{$type}")->with('import_id', $import->id)->with('success', 'Archivo recibido. La validación se ejecuta en segundo plano.');
    }

    public function showImport(Request $request, Importacion $importacion)
    {
        $this->authorizeImport($request, $importacion);

        // A failed 500k-row validation can have many errors. The detail screen
        // only needs to know whether its private CSV is available; never load
        // the complete error collection into the web request.
        $importacion->loadCount('errores');

        return view('cargas.importacion', ['import' => $importacion]);
    }
    public function status(Request $request, Importacion $importacion) { $this->authorizeImport($request, $importacion); return response()->json($this->statusPayload($importacion->fresh())); }

    public function errors(Request $request, Importacion $importacion)
    {
        $this->authorizeImport($request, $importacion); abort_unless($importacion->errores()->exists(), 404);
        return response()->streamDownload(function () use ($importacion): void {
            $out = fopen('php://output', 'wb'); fputcsv($out, ['FILA', 'CAMPO', 'CODIGO', 'MOTIVO', 'VALOR']);
            $importacion->errores()->orderBy('fila')->orderBy('id')->chunkById(1000, function ($errors) use ($out): void { foreach ($errors as $error) fputcsv($out, [$error->fila, $error->campo, $error->codigo, $error->motivo, $error->valor]); });
            fclose($out);
        }, 'errores_importacion_'.$importacion->id.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'private, no-store']);
    }

    // DATA and payment compatibility routes now enqueue the scalable pipeline.
    public function storeData(MassImportRequest $request) { return $this->storeImport($request, 'asignacion'); }
    public function storePagos(MassImportRequest $request) { return $this->storeImport($request, 'pagos'); }
    public function storeSeguimientos(UploadRequest $request, ImportService $imports) { return $this->legacyStore($request, $imports, 'seguimientos'); }
    public function storeEvolutivo(UploadRequest $request, ImportService $imports) { return $this->legacyStore($request, $imports, 'evolutivo'); }

    private function legacyStore(UploadRequest $request, ImportService $imports, string $type) { return response()->json(['success' => true, 'summary' => $imports->import($request->file('file'), $type, $request->user(), $request->validated('modo', 'merge'))]); }
    private function history(Request $request, ?string $type = null) { return Importacion::query()->when(! $request->user()->isAdmin(), fn ($query) => $query->where('usuario_id', $request->user()->id))->when($type, fn ($query) => $query->where('tipo', $type))->with('usuario:id,nombre,usuario')->latest()->paginate(10)->withQueryString(); }
    private function authorizeImport(Request $request, Importacion $importacion): void { abort_unless($importacion->canBeViewedBy($request->user()), 403); }
    private function statusPayload(Importacion $import): array { return ['id' => $import->id, 'estado' => $import->estado, 'etapa' => $import->etapa, 'periodo' => $import->periodo, 'total_filas' => $import->total_filas, 'filas_procesadas' => $import->filas_procesadas, 'filas_validas' => $import->filas_validas, 'insertadas' => $import->insertadas, 'actualizadas' => $import->actualizadas, 'omitidas' => $import->omitidas, 'fallidas' => $import->fallidas, 'mensaje' => $import->mensaje, 'errores_url' => $import->errores()->exists() ? route('cargas.importaciones.errores', $import) : null, 'detail_url' => route('cargas.importaciones.show', $import)]; }
}
