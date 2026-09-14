<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadRequest;
use App\Services\{DocumentService, ImportService};
use Illuminate\Http\Request;
use Inertia\Inertia;

class CargasController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Cargas/Index', [
            'types' => array_keys(ImportService::HEADERS),
            'headers' => ImportService::HEADERS,
            'isAdmin' => $request->user()->isAdmin(),
        ]);
    }

    public function plantilla(Request $request, ImportService $imports, DocumentService $documents)
    {
        $type = (string) $request->query('tipo', 'data');
        abort_unless(array_key_exists($type, ImportService::HEADERS), 422);

        return $documents->download($imports->template($type));
    }

    public function storeData(UploadRequest $request, ImportService $imports)
    {
        return $this->store($request, $imports, 'data');
    }

    public function storePagos(UploadRequest $request, ImportService $imports)
    {
        return $this->store($request, $imports, 'pagos');
    }

    public function storeSeguimientos(UploadRequest $request, ImportService $imports)
    {
        return $this->store($request, $imports, 'seguimientos');
    }

    public function storeEvolutivo(UploadRequest $request, ImportService $imports)
    {
        return $this->store($request, $imports, 'evolutivo');
    }

    private function store(UploadRequest $request, ImportService $imports, string $type)
    {
        $summary = $imports->import(
            $request->file('file'),
            $type,
            $request->user(),
            $request->validated('modo', 'merge'),
        );

        return response()->json(['success' => true, 'summary' => $summary]);
    }
}
