<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('failed_jobs', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        Schema::create('importaciones', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->unsignedBigInteger('usuario_id')->index();
            $table->string('tipo', 30)->index();
            $table->string('nombre_archivo', 180);
            $table->string('archivo_path', 255);
            $table->unsignedBigInteger('tamano_bytes');
            $table->string('estado', 30)->index();
            $table->string('etapa', 30)->nullable();
            $table->char('periodo', 8)->nullable()->index();
            $table->unsignedInteger('total_filas')->nullable();
            $table->unsignedInteger('filas_procesadas')->default(0);
            $table->unsignedInteger('filas_validas')->default(0);
            $table->unsignedInteger('insertadas')->default(0);
            $table->unsignedInteger('actualizadas')->default(0);
            $table->unsignedInteger('omitidas')->default(0);
            $table->unsignedInteger('fallidas')->default(0);
            $table->text('mensaje')->nullable();
            $table->timestamp('iniciada_en')->nullable();
            $table->timestamp('finalizada_en')->nullable();
            $table->timestamps();
        });

        Schema::create('importacion_errores', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('importacion_id')->index();
            $table->unsignedInteger('fila');
            $table->string('campo', 60)->nullable();
            $table->string('codigo', 80);
            $table->string('motivo', 500);
            $table->string('valor', 255)->nullable();
            $table->timestamp('creado_en')->useCurrent();
            $table->index(['importacion_id', 'fila']);
        });
    }

    public function down(): void
    {
        if (! app()->environment('testing')) {
            throw new RuntimeException('Rollback de la cola de importaciones deshabilitado fuera de tests.');
        }

        Schema::dropIfExists('importacion_errores');
        Schema::dropIfExists('importaciones');
        Schema::dropIfExists('failed_jobs');
        Schema::dropIfExists('jobs');
    }
};
