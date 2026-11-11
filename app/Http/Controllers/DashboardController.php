<?php

namespace App\Http\Controllers;

use App\Services\DashboardMetricsService;

class DashboardController extends Controller
{
    public function index(\Illuminate\Http\Request $request, DashboardMetricsService $metrics)
    {
        return view('dashboard.index', [
            'generationStats' => $metrics->for($request->user()),
            'recentPayments' => $metrics->recentPayments($request->user()),
        ]);
    }
}
