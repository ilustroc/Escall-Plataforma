<?php

namespace App\Http\Controllers;

use App\Models\{Documento, Propuesta};
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReporteController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'tab' => ['nullable', 'in:acuerdos,propuestas'],
            'desde' => ['nullable', 'date_format:Y-m-d'],
            'hasta' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:desde'],
        ]);
        $tab = $data['tab'] ?? 'acuerdos';
        $between = function ($query) use ($data) {
            $query->when($data['desde'] ?? null, fn ($q, $date) => $q->whereDate('creado_en', '>=', $date))
                ->when($data['hasta'] ?? null, fn ($q, $date) => $q->whereDate('creado_en', '<=', $date));
        };

        $scope = fn ($query) => $request->user()->isAdmin()
            ? $query
            : $query->where('usuario_id', $request->user()->id);
        $empty = ['data' => [], 'links' => [], 'total' => 0, 'from' => null, 'to' => null];
        $documents = $tab === 'acuerdos'
            ? Documento::query()->select(['id','cliente_id','usuario_id','nombre_archivo','tipo_mime','creado_en'])
                ->with(['cliente:id,documento,nombre', 'usuario:id,nombre,usuario'])->tap($scope)->tap($between)
                ->orderByDesc('creado_en')->paginate(10, ['*'], 'acuerdos_page')->withQueryString()
            : $empty;
        $proposals = $tab === 'propuestas'
            ? Propuesta::query()->select(['id','cliente_id','usuario_id','propuesta_cliente','fecha_pago','nombre_archivo','tipo_archivo','creado_en'])
                ->with(['cliente:id,documento,nombre', 'usuario:id,nombre,usuario'])->tap($scope)->tap($between)
                ->orderByDesc('creado_en')->paginate(10, ['*'], 'propuestas_page')->withQueryString()
            : $empty;

        return Inertia::render('Reportes/Index', [
            'tab' => $tab,
            'filters' => ['desde' => $data['desde'] ?? null, 'hasta' => $data['hasta'] ?? null],
            'documents' => $documents,
            'proposals' => $proposals,
        ]);
    }

    public function descargarDocumento(Request $request, DocumentService $documents)
    {
        $data = $request->validate(['id' => ['required', 'integer', 'exists:documentos,id']]);
        $document = Documento::findOrFail($data['id']);
        $this->authorize('view', $document);

        return $documents->download([
            'bytes' => $document->contenido,
            'filename' => $document->nombre_archivo,
            'mime' => $document->tipo_mime,
        ]);
    }

    public function eliminarDocumento(Request $request)
    {
        $data = $request->validate(['id' => ['required', 'integer', 'exists:documentos,id']]);
        $document = Documento::findOrFail($data['id']);
        $this->authorize('delete', $document);
        $document->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Documento eliminado.');
    }
}
