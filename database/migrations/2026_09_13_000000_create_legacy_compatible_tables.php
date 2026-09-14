<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Installation clean only. Never run on the existing production schema.
return new class extends Migration {
    public function up(): void
    {
        foreach (['usuarios','cartera_clientes','documentos','propuestas','sustentos','pagos','seguimientos','seguimientos_evolutivo'] as $table) {
            if (Schema::hasTable($table)) throw new RuntimeException('Instalación limpia únicamente: ya existe una tabla legacy.');
        }
        Schema::create('usuarios', function (Blueprint $t) {
            $t->id(); $t->string('usuario')->unique(); $t->string('nombre'); $t->string('contrasena');
            $t->string('rol', 30)->default('asesor'); $t->boolean('activo')->nullable()->default(true);
        });
        Schema::create('cartera_clientes', function (Blueprint $t) {
            $t->id(); $t->string('codigo', 100)->unique(); $t->string('dni', 30)->index();
            foreach (['cartera','titular','entidad','cosecha','producto','sub_producto','historico','departamento','rango','campana'] as $c) $t->string($c)->nullable();
            $t->decimal('deuda_total', 18, 2)->nullable(); $t->decimal('deuda_capital', 18, 2)->nullable();
        });
        Schema::create('documentos', function (Blueprint $t) {
            $t->id(); $t->string('codigo', 8)->unique(); $t->unsignedBigInteger('cliente_id')->index(); $t->unsignedBigInteger('usuario_id')->index();
            $t->string('nombre_archivo'); $t->string('tipo_mime'); $t->binary('contenido'); $t->timestamp('creado_en')->useCurrent();
        });
        Schema::create('propuestas', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('cliente_id')->index(); $t->unsignedBigInteger('usuario_id')->index();
            $t->decimal('propuesta_cliente', 18, 2); $t->date('fecha_pago'); $t->text('observacion')->nullable(); $t->text('cuentas_seleccionadas');
            $t->binary('archivo_blob')->nullable(); $t->string('nombre_archivo')->nullable(); $t->string('tipo_archivo')->nullable(); $t->string('ruta_archivo')->nullable();
            $t->timestamp('creado_en')->useCurrent();
        });
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::getConnection()->statement('ALTER TABLE documentos MODIFY contenido LONGBLOB NOT NULL');
            Schema::getConnection()->statement('ALTER TABLE propuestas MODIFY archivo_blob LONGBLOB NULL');
        }
        Schema::create('sustentos', function (Blueprint $t) { $t->id(); $t->string('sustento'); $t->text('texto'); });
        Schema::create('pagos', function (Blueprint $t) {
            $t->id(); $t->string('codigo', 100); $t->date('fecha'); $t->decimal('monto', 18, 2);
            $t->string('asesor')->nullable(); $t->string('operacion', 100)->default(''); $t->text('observacion')->nullable(); $t->unsignedBigInteger('usuario_id');
            $t->unique(['codigo','fecha','monto','operacion']);
        });
        Schema::create('seguimientos', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('usuario_id')->index(); $t->string('codigo', 100)->index(); $t->date('fecha')->index(); $t->time('hora')->nullable();
            $t->string('tipo', 30); $t->text('descripcion')->nullable(); $t->string('estado')->nullable(); $t->timestamps();
        });
        Schema::create('seguimientos_evolutivo', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('usuario_id')->index(); $t->string('dni', 30); $t->string('tipo_acuerdo', 100); $t->date('fecha')->index();
            $t->decimal('monto', 18, 2)->nullable(); $t->string('estado')->nullable(); $t->string('gestion')->nullable(); $t->timestamps();
            $t->unique(['usuario_id','dni','tipo_acuerdo','fecha'], 'evolutivo_usuario_acuerdo_fecha_unique');
        });
    }
    public function down(): void
    {
        if (!app()->environment('testing')) throw new RuntimeException('Rollback destructivo deshabilitado fuera de tests.');
        foreach (['seguimientos_evolutivo','seguimientos','pagos','sustentos','propuestas','documentos','cartera_clientes','usuarios'] as $table) Schema::dropIfExists($table);
    }
};
