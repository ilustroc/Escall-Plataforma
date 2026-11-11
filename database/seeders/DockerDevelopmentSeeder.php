<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use LogicException;

/**
 * Deliberately small, local-only fixture for an empty Docker test schema.
 * It is never called automatically.
 */
class DockerDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        if (!app()->environment('local') || config('database.default') !== 'mysql' || config('database.connections.mysql.host') !== 'db') {
            throw new LogicException('Este seeder sólo puede ejecutarse contra MySQL del contenedor Docker local.');
        }

        User::query()->firstOrCreate(
            ['usuario' => 'demo.escall'],
            [
                'nombre' => 'Usuario de demostración',
                'contrasena' => Hash::make('DockerOnlyLogin2026!'),
                'rol' => 'asesor',
                'activo' => 1,
            ],
        );

        $adminUser = trim((string) env('ESCALL_DEMO_ADMIN_USER', ''));
        $adminPassword = (string) env('ESCALL_DEMO_ADMIN_PASSWORD', '');
        if ($adminUser === '' && $adminPassword === '') {
            return;
        }
        if ($adminUser === '' || $adminPassword === '') {
            throw new LogicException('Defina ESCALL_DEMO_ADMIN_USER y ESCALL_DEMO_ADMIN_PASSWORD para crear el administrador demo local.');
        }

        $admin = User::query()->firstOrNew(['usuario' => $adminUser]);
        $admin->fill([
            'nombre' => (string) env('ESCALL_DEMO_ADMIN_NAME', 'Administrador de demostración'),
            'rol' => 'administrador',
            'activo' => 1,
        ]);
        if (! $admin->exists || ! Hash::check($adminPassword, (string) $admin->contrasena)) {
            $admin->contrasena = Hash::make($adminPassword);
        }
        $admin->save();
    }
}
