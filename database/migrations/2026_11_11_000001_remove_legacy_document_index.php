<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('Cartera_Mensual')) return;

        Schema::table('Cartera_Mensual', function (Blueprint $table): void {
            $table->dropIndex('cartera_clientes_dni_index');
        });
    }

    public function down(): void
    {
        if (! app()->environment('testing')) throw new RuntimeException('Rollback de índices de cartera deshabilitado fuera de tests.');

        Schema::table('Cartera_Mensual', function (Blueprint $table): void {
            $table->index('documento', 'cartera_clientes_dni_index');
        });
    }
};
