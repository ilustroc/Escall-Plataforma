<?php

namespace App\Services;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class CarteraMensualQuery
{
    /**
     * One operational row per account code. Filters must be added only after
     * this query so a historical match can never replace the current version.
     */
    public static function vigentes(): Builder
    {
        return Cliente::query()->whereNotExists(function ($query): void {
            $query->select(DB::raw(1))
                ->from('Cartera_Mensual as cartera_mas_reciente')
                ->whereColumn('cartera_mas_reciente.codigo', 'Cartera_Mensual.codigo')
                ->where(function ($newer): void {
                    $newer->whereColumn('cartera_mas_reciente.FEC_PRO', '>', 'Cartera_Mensual.FEC_PRO')
                        ->orWhere(function ($samePeriod): void {
                            $samePeriod->whereColumn('cartera_mas_reciente.FEC_PRO', '=', 'Cartera_Mensual.FEC_PRO')
                                ->whereColumn('cartera_mas_reciente.id', '>', 'Cartera_Mensual.id');
                        });
                });
        });
    }
}
