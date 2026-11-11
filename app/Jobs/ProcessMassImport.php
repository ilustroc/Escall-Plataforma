<?php

namespace App\Jobs;

use App\Models\Importacion;
use App\Services\MassImportProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessMassImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 900;
    public int $tries = 2;
    public array $backoff = [30, 180];

    public function __construct(public readonly int $importacionId) {}

    public function handle(MassImportProcessor $processor): void
    {
        $processor->process(Importacion::findOrFail($this->importacionId));
    }

    public function failed(Throwable $exception): void
    {
        Importacion::whereKey($this->importacionId)->update([
            'estado' => 'fallido', 'etapa' => 'fallido',
            'mensaje' => 'La importación no pudo finalizar. Revise el detalle o vuelva a intentarlo.',
            'finalizada_en' => now(),
        ]);
    }
}
