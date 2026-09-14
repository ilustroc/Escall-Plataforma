<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClienteSearchRequest;
use App\Services\{ClientService, DashboardMetricsService};
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(ClienteSearchRequest $request, ClientService $clients, DashboardMetricsService $metrics)
    {
        $filters = $request->safe()->only(['dni', 'codigo', 'titular', 'cartera']);

        return Inertia::render('Dashboard/Index', [
            'filters' => $filters,
            'clientes' => $clients->grouped($filters),
            'carteras' => \App\Models\Cliente::query()->whereNotNull('cartera')->distinct()->orderBy('cartera')->pluck('cartera')->values(),
            'stats' => $metrics->for($request->user()),
            'recentActivity' => $metrics->recentActivity($request->user()),
        ]);
    }
}
