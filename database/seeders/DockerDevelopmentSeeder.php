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
    }
}
