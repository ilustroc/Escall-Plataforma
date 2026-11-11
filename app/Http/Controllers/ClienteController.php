<?php

namespace App\Http\Controllers;

use App\Http\Requests\{ClienteAutocompleteRequest, ClienteSearchRequest, WordRequest};
use App\Models\Cliente;
use App\Services\{CarteraMensualQuery, ClientService, DocumentService};
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClienteController extends Controller
{
    public function show(Request $request)
    {
        $documento = trim((string) $request->query('dni'));
        abort_if($documento === '', 404);
        $accounts = CarteraMensualQuery::vigentes()->where('documento', $documento)->orderBy('codigo')->get(['id','cartera','documento','nombre','codigo','entidad','cosecha','producto','sub_producto','departamento','deuda_total','deuda_capital','campana','FEC_PRO']);
        abort_if($accounts->isEmpty(), 404);

        return Inertia::render('Clientes/Show', [
            'cliente' => $accounts->first(),
            'cuentas' => $accounts,
            'cuentasDeposito' => collect(config('platform.bank_accounts', []))
                ->filter(fn ($account) => filled($account['value'] ?? null))
                ->map(fn ($account, $id) => ['id' => $id, 'label' => $account['label']])
                ->values(),
        ]);
    }

    public function search(ClienteSearchRequest $request, ClientService $clients)
    {
        $filters = $request->safe()->only(['documento', 'codigo', 'nombre', 'cartera', 'dni', 'titular']);
        $rows = $clients->query($filters)->orderBy('documento')->orderBy('codigo')->limit(200)->get(['id','documento','nombre','codigo','cartera','entidad','producto','sub_producto','deuda_total','deuda_capital']);

        return response()->json(['ok' => true, 'data' => $rows]);
    }

    public function suggestions(ClienteAutocompleteRequest $request)
    {
        $term = mb_strtolower($request->validated('q'), 'UTF-8');
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';
        $prefix = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $term).'%';

        $rows = CarteraMensualQuery::vigentes()
            ->where(function ($query) use ($like): void {
                $query->whereRaw('LOWER(documento) LIKE ? ESCAPE \'\\\'', [$like])
                    ->orWhereRaw('LOWER(codigo) LIKE ? ESCAPE \'\\\'', [$like])
                    ->orWhereRaw('LOWER(nombre) LIKE ? ESCAPE \'\\\'', [$like]);
            })
            ->orderByRaw(
                'CASE WHEN LOWER(documento) = ? OR LOWER(codigo) = ? THEN 0 WHEN LOWER(documento) LIKE ? ESCAPE \'\\\' OR LOWER(codigo) LIKE ? ESCAPE \'\\\' OR LOWER(nombre) LIKE ? ESCAPE \'\\\' THEN 1 ELSE 2 END',
                [$term, $term, $prefix, $prefix, $prefix],
            )
            ->orderBy('documento')
            ->orderBy('codigo')
            ->limit(10)
            ->get(['id', 'documento', 'nombre', 'codigo', 'cartera']);

        return response()->json(['data' => $rows]);
    }

    public function generateWord(WordRequest $request, DocumentService $documents)
    {
        $document = $documents->word($request->validated(), $request->user());

        return response()->json([
            'success' => true,
            'id' => $document->id,
            'download_url' => route('reportes.descargar', ['id' => $document->id]),
        ]);
    }
}
