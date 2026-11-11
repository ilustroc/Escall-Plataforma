<?php

namespace App\Http\Controllers;

use App\Services\DashboardMetricsService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardMetricsService $metrics)
    {
        if ($request->header('X-Inertia')) {
            return Inertia::location(route('dashboard'));
        }

        return view('dashboard.index', [
            'generationStats' => $metrics->for($request->user()),
            'recentPayments' => $metrics->recentPayments($request->user()),
        ]);
    }
}
