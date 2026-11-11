<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('cartera_clientes')) {
            return;
        }

        // A pre-existing row has no trustworthy source for FEC_PRO in the legacy schema.
        // Refuse a partial or invented historical migration rather than silently changing history.
        if (DB::table('cartera_clientes')->exists()) {
            throw new RuntimeException(
                'La cartera legacy contiene registros sin FEC_PRO verificable. '
                .'Prepare un mapeo de fecha de proceso y una copia de seguridad antes de ejecutar esta migración.'
            );
        }

        Schema::rename('cartera_clientes', 'Cartera_Mensual');

        Schema::table('Cartera_Mensual', function (Blueprint $table): void {
            $table->dropUnique('cartera_clientes_codigo_unique');
            $table->renameColumn('dni', 'documento');
            $table->renameColumn('titular', 'nombre');
            $table->dropColumn(['historico', 'rango']);
            $table->char('FEC_PRO', 8);
            $table->timestamp('fecha_carga')->nullable()->useCurrent();
            $table->unique(['codigo', 'FEC_PRO'], 'Cartera_Mensual_codigo_fec_pro_unique');
            $table->index('documento', 'Cartera_Mensual_documento_index');
            $table->index(['codigo', 'FEC_PRO', 'id'], 'Cartera_Mensual_codigo_fec_pro_id_index');
        });
    }

    public function down(): void
    {
        if (! app()->environment('testing')) {
            throw new RuntimeException('Rollback de cartera mensual deshabilitado fuera de tests.');
        }

        Schema::dropIfExists('Cartera_Mensual');
    }
};
