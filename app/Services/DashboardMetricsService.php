<?php

namespace App\Services;

use App\Models\{Cliente, Pago, Seguimiento, SeguimientoEvolutivo, User};

class DashboardMetricsService
{
    public function for(User $user): array
    {
        $portfolio = Cliente::query()
            ->selectRaw('COUNT(*) as cuentas, COUNT(DISTINCT dni) as clientes, COALESCE(SUM(deuda_total), 0) as cartera_total')
            ->first();
        $scope = fn ($query) => $user->isAdmin() ? $query : $query->where('usuario_id', $user->id);
        $payments = Pago::query()->selectRaw('COUNT(*) as registros, COALESCE(SUM(monto), 0) as monto')->first();
        $followups = Seguimiento::query()->tap($scope);
        $evolution = SeguimientoEvolutivo::query()->tap($scope);

        return [
            'cartera_total' => (float) $portfolio->cartera_total,
            'clientes' => (int) $portfolio->clientes,
            'cuentas' => (int) $portfolio->cuentas,
            'pagos_registrados' => (int) $payments->registros,
            'pagos_total' => (float) $payments->monto,
            'seguimientos_hoy' => (clone $followups)->whereDate('fecha', today())->count(),
            'seguimientos_registrados' => (clone $followups)->count(),
            'pendientes' => (clone $evolution)->where('gestion', 'Pendiente')->count(),
        ];
    }

    public function recentActivity(User $user): array
    {
        $query = Seguimiento::query()
            ->select(['id', 'usuario_id', 'codigo', 'fecha', 'hora', 'tipo', 'descripcion', 'estado'])
            ->with('cliente:id,codigo,dni,titular')
            ->orderByDesc('fecha')->orderByDesc('hora')->orderByDesc('id');
        if (! $user->isAdmin()) {
            $query->where('usuario_id', $user->id);
        }

        return $query->limit(6)->get()->all();
    }
}
