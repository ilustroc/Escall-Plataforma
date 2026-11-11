<?php

namespace App\Services;

use App\Models\{Documento, Pago, Propuesta, User};
use Illuminate\Support\Facades\DB;

class DashboardMetricsService
{
    public function for(User $user): array
    {
        $scope = fn ($query) => $user->isAdmin() ? $query : $query->where('usuario_id', $user->id);
        $documents = Documento::query()->tap($scope)->count();
        $proposals = Propuesta::query()->tap($scope)->count();

        return [
            'documents' => $documents,
            'proposals' => $proposals,
            'total' => $documents + $proposals,
        ];
    }

    public function recentPayments(User $user): array
    {
        $clientesVigentes = CarteraMensualQuery::vigentes()
            ->select(['id', 'codigo', 'documento', 'nombre', 'cartera']);

        $query = Pago::query()
            ->leftJoinSub($clientesVigentes, 'cliente', 'cliente.codigo', '=', 'pagos.codigo')
            ->select([
                'pagos.id', 'pagos.usuario_id', 'pagos.codigo', 'pagos.fecha', 'pagos.monto',
                DB::raw('cliente.documento as cliente_dni'),
                DB::raw('cliente.nombre as cliente_titular'),
                DB::raw('cliente.cartera as cliente_cartera'),
            ])
            ->orderByDesc('pagos.fecha')
            ->orderByDesc('pagos.id');
        if (! $user->isAdmin()) {
            $query->where('pagos.usuario_id', $user->id);
        }

        return $query->limit(6)->get()->all();
    }
}
