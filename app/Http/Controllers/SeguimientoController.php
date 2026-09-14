<?php

namespace App\Http\Controllers;

use App\Http\Requests\SeguimientoRequest;
use App\Models\{Seguimiento, SeguimientoEvolutivo};
use Illuminate\Http\Request;
use Inertia\Inertia;

class SeguimientoController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Seguimientos/Index', ['isAdmin' => $request->user()->isAdmin()]);
    }

    public function list(Request $request)
    {
        $filters = $request->validate(['todos' => ['nullable', 'boolean'], 'fecha' => ['nullable', 'date_format:Y-m-d']]);
        $query = Seguimiento::query()->with('cliente:id,codigo,dni,titular')->orderByDesc('fecha')->orderByDesc('hora')->orderByDesc('id');
        if (!$request->user()->isAdmin() || !$request->boolean('todos')) {
            $query->where('usuario_id', $request->user()->id);
        }
        $query->when($filters['fecha'] ?? null, fn ($q, $date) => $q->whereDate('fecha', $date));

        return response()->json(['data' => $query->limit(500)->get()]);
    }

    public function store(SeguimientoRequest $request)
    {
        $followup = Seguimiento::create($request->validated() + ['usuario_id' => $request->user()->id]);

        return response()->json(['success' => true, 'id' => $followup->id], 201);
    }

    public function destroy(Request $request)
    {
        $data = $request->validate(['id' => ['required', 'integer', 'exists:seguimientos,id']]);
        $followup = Seguimiento::findOrFail($data['id']);
        $this->authorize('delete', $followup);
        $followup->delete();

        return response()->json(['success' => true]);
    }

    public function evolutivo(Request $request)
    {
        $filters = $request->validate(['todos' => ['nullable', 'boolean'], 'fecha' => ['nullable', 'date_format:Y-m-d']]);
        $query = SeguimientoEvolutivo::query()->orderByDesc('fecha')->orderByDesc('id');
        if (!$request->user()->isAdmin() || !$request->boolean('todos')) {
            $query->where('usuario_id', $request->user()->id);
        }
        $query->when($filters['fecha'] ?? null, fn ($q, $date) => $q->whereDate('fecha', $date));

        return response()->json(['data' => $query->limit(1000)->get()]);
    }
}
