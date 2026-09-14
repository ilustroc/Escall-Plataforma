<?php

namespace App\Http\Controllers;

use App\Http\Requests\PropuestaRequest;
use App\Models\{Cliente, Propuesta, Sustento};
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PropuestaController extends Controller
{
    public function form(Request $request)
    {
        $dni = trim((string) $request->query('dni'));
        if ($dni === '' && $request->filled('cliente_id')) {
            $dni = (string) Cliente::findOrFail($request->integer('cliente_id'))->dni;
        }
        abort_if($dni === '', 404);
        $accounts = Cliente::query()->where('dni', $dni)->orderBy('codigo')->get(['id','cartera','dni','titular','codigo','entidad','cosecha','producto','sub_producto','historico','departamento','rango','deuda_total','deuda_capital','campana']);
        abort_if($accounts->isEmpty(), 404);

        return Inertia::render('Propuestas/Form', [
            'cliente' => $accounts->first(),
            'cuentas' => $accounts,
            'sustentos' => Sustento::query()->orderBy('sustento')->get(['id', 'sustento', 'texto']),
        ]);
    }

    public function store(PropuestaRequest $request, DocumentService $documents)
    {
        $proposal = $documents->proposal($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'id' => $proposal->id,
            'download_url' => route('propuestas.excel', ['propuesta_id' => $proposal->id]),
        ]);
    }

    public function excel(Request $request, DocumentService $documents)
    {
        $data = $request->validate(['propuesta_id' => ['required', 'integer', 'exists:propuestas,id']]);
        $proposal = Propuesta::with('cliente')->findOrFail($data['propuesta_id']);
        $this->authorize('view', $proposal);

        return $documents->download($documents->proposalFile($proposal));
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['id' => ['required', 'integer', 'exists:propuestas,id']]);
        $proposal = Propuesta::findOrFail($data['id']);
        $this->authorize('delete', $proposal);
        $proposal->delete();

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Propuesta eliminada.');
    }
}
