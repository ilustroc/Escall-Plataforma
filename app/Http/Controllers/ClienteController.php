<?php

namespace App\Http\Controllers;

use App\Http\Requests\{ClienteSearchRequest, WordRequest};
use App\Models\Cliente;
use App\Services\{ClientService, DocumentService};
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClienteController extends Controller
{
    public function show(Request $request)
    {
        $dni = trim((string) $request->query('dni'));
        abort_if($dni === '', 404);
        $accounts = Cliente::query()->where('dni', $dni)->orderBy('codigo')->get();
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
        $filters = $request->safe()->only(['dni', 'codigo', 'titular', 'cartera']);
        $rows = $clients->query($filters)->orderBy('dni')->orderBy('codigo')->limit(200)->get();

        return response()->json(['ok' => true, 'data' => $rows]);
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
