<?php

namespace Tests\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

trait LegacyFixtures
{
    protected function client(array $overrides = []): int
    {
        if (array_key_exists('dni', $overrides)) { $overrides['documento'] = $overrides['dni']; unset($overrides['dni']); }
        if (array_key_exists('titular', $overrides)) { $overrides['nombre'] = $overrides['titular']; unset($overrides['titular']); }

        return DB::table('Cartera_Mensual')->insertGetId(array_replace([
            'documento' => '12345678', 'codigo' => 'TEST-ACCOUNT-001', 'nombre' => 'Cliente de pruebas',
            'cartera' => 'Cartera de pruebas', 'entidad' => 'Entidad de pruebas', 'cosecha' => '2026',
            'producto' => 'Tarjeta', 'sub_producto' => 'Clásica', 'departamento' => 'Lima',
            'deuda_total' => 2000, 'deuda_capital' => 1500, 'campana' => 'Pruebas', 'FEC_PRO' => '20261101',
        ], $overrides));
    }

    protected function document(User $owner, int $clientId, array $overrides = []): int
    {
        return DB::table('documentos')->insertGetId(array_replace([
            'codigo' => strtoupper(bin2hex(random_bytes(4))), 'cliente_id' => $clientId, 'usuario_id' => $owner->id,
            'nombre_archivo' => 'acuerdo-prueba.docx',
            'tipo_mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'contenido' => 'private-document-fixture-bytes', 'creado_en' => now(),
        ], $overrides));
    }

    protected function proposal(User $owner, int $clientId, array $overrides = []): int
    {
        return DB::table('propuestas')->insertGetId(array_replace([
            'cliente_id' => $clientId, 'usuario_id' => $owner->id, 'propuesta_cliente' => 500,
            'fecha_pago' => now()->addDay()->toDateString(), 'observacion' => 'Propuesta de pruebas',
            'cuentas_seleccionadas' => json_encode([['codigo' => 'TEST-ACCOUNT-001', 'propuesta' => 500]]),
            'creado_en' => now(),
        ], $overrides));
    }

    protected function followup(User $owner, array $overrides = []): int
    {
        return DB::table('seguimientos')->insertGetId(array_replace([
            'usuario_id' => $owner->id, 'codigo' => 'TEST-ACCOUNT-001', 'fecha' => now()->toDateString(),
            'hora' => '10:30', 'tipo' => 'seguimiento', 'descripcion' => 'Llamada de pruebas',
            'estado' => 'pendiente', 'created_at' => now(), 'updated_at' => now(),
        ], $overrides));
    }
}
