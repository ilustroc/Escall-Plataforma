<?php

namespace Tests\Feature;

use App\Models\Documento;
use App\Models\Seguimiento;
use App\Models\User;
use App\Services\CarteraMensualQuery;
use App\Services\DashboardMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\LegacyFixtures;
use Tests\TestCase;

class CarteraMensualTest extends TestCase
{
    use RefreshDatabase, LegacyFixtures;

    public function test_monthly_schema_uses_the_requested_table_and_columns(): void
    {
        $this->assertTrue(Schema::hasTable('Cartera_Mensual'));
        $this->assertFalse(Schema::hasTable('cartera_clientes'));
        $this->assertTrue(Schema::hasColumns('Cartera_Mensual', ['documento', 'nombre', 'codigo', 'FEC_PRO', 'fecha_carga']));
        $this->assertFalse(Schema::hasColumn('Cartera_Mensual', 'historico'));
        $this->assertFalse(Schema::hasColumn('Cartera_Mensual', 'rango'));
    }

    public function test_import_keeps_each_month_and_updates_only_the_matching_period(): void
    {
        $this->actingAs(User::factory()->administrator()->create());
        $headers = 'cartera,documento,nombre,codigo,entidad,cosecha,producto,sub_producto,departamento,deuda_total,deuda_capital,campana,FEC_PRO';

        $this->postJson('/cargas/data', ['file' => $this->csv('june.csv', [$headers, 'C,123127,AMMED ALI,001234,E,2026,P,S,Lima,100,90,C,20260601'])])
            ->assertOk()->assertJsonPath('summary.inserted', 1)->assertJsonPath('summary.period', '20260601');
        $first = DB::table('Cartera_Mensual')->where('codigo', '001234')->where('FEC_PRO', '20260601')->value('id');

        $this->postJson('/cargas/data', ['file' => $this->csv('november.csv', [$headers, 'C,123127,AMMED ALI,001234,E,2026,P,S,Lima,220,190,C,20261101'])])
            ->assertOk()->assertJsonPath('summary.inserted', 1);
        $this->postJson('/cargas/data', ['file' => $this->csv('november-update.csv', [$headers, 'C,123127,AMMED ALI ACTUALIZADO,001234,E,2026,P,S,Lima,250,200,C,20261101'])])
            ->assertOk()->assertJsonPath('summary.updated', 1);

        $this->assertDatabaseCount('Cartera_Mensual', 2);
        $this->assertDatabaseHas('Cartera_Mensual', ['id' => $first, 'FEC_PRO' => '20260601', 'deuda_total' => 100]);
        $this->assertDatabaseHas('Cartera_Mensual', ['codigo' => '001234', 'FEC_PRO' => '20261101', 'nombre' => 'AMMED ALI ACTUALIZADO']);
    }

    public function test_operational_queries_choose_current_accounts_before_filtering(): void
    {
        $oldId = $this->client(['codigo' => '001234', 'documento' => '11111111', 'nombre' => 'Nombre anterior', 'FEC_PRO' => '20260601']);
        $currentId = $this->client(['codigo' => '001234', 'documento' => '99999999', 'nombre' => 'Nombre vigente', 'FEC_PRO' => '20261101']);
        $otherId = $this->client(['codigo' => '009999', 'documento' => '99999999', 'nombre' => 'Nombre vigente', 'FEC_PRO' => '20261101']);

        $this->assertSame([$currentId, $otherId], CarteraMensualQuery::vigentes()->orderBy('codigo')->pluck('id')->all());
        $this->actingAs(User::factory()->create())
            ->getJson('/api/clientes/sugerencias?q=11111111')
            ->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/clientes/sugerencias?q=99999999')
            ->assertOk()->assertJsonCount(2, 'data')->assertJsonFragment(['codigo' => '001234', 'documento' => '99999999']);

        $this->assertNotSame($oldId, $currentId);
    }

    public function test_historical_documents_keep_their_original_client_id(): void
    {
        $oldId = $this->client(['codigo' => 'DOC-001', 'FEC_PRO' => '20260601', 'nombre' => 'Nombre histórico']);
        $this->client(['codigo' => 'DOC-001', 'FEC_PRO' => '20261101', 'nombre' => 'Nombre vigente']);
        $owner = User::factory()->create();
        $documentId = $this->document($owner, $oldId);

        $document = Documento::with('cliente')->findOrFail($documentId);
        $this->assertSame($oldId, $document->cliente_id);
        $this->assertSame('Nombre histórico', $document->cliente->nombre);
    }

    public function test_new_documents_and_activity_use_the_current_version_without_duplicate_records(): void
    {
        $oldId = $this->client(['codigo' => 'CURRENT-001', 'FEC_PRO' => '20260601', 'nombre' => 'Titular anterior']);
        $currentId = $this->client(['codigo' => 'CURRENT-001', 'FEC_PRO' => '20261101', 'nombre' => 'Titular vigente']);
        $owner = User::factory()->create();
        config(['platform.bank_accounts' => ['bcp' => ['label' => 'Banco de pruebas', 'value' => 'TEST']]]);

        $this->actingAs($owner)->postJson('/clientes/generar-word', [
            'cliente_id' => $currentId, 'tipo_documento' => 'cancelacion', 'cuentas_seleccionadas' => ['CURRENT-001'],
            'tipo_facilidad' => 'Cancelación', 'fecha_solicitud' => '2026-11-01', 'cuenta_banco' => 'bcp',
            'usar_cuotas' => false, 'cancelacion' => 100, 'fecha_pago' => '2026-11-02',
        ])->assertSuccessful();
        $this->assertDatabaseHas('documentos', ['cliente_id' => $currentId]);
        $this->assertDatabaseMissing('documentos', ['cliente_id' => $oldId]);

        DB::table('pagos')->insert(['codigo' => 'CURRENT-001', 'fecha' => '2026-11-03', 'monto' => 90, 'operacion' => 'ONE', 'usuario_id' => $owner->id]);
        Seguimiento::create(['codigo' => 'CURRENT-001', 'fecha' => '2026-11-03', 'hora' => '09:00', 'tipo' => 'seguimiento', 'usuario_id' => $owner->id]);

        $payments = app(DashboardMetricsService::class)->recentPayments($owner);
        $this->assertCount(1, $payments);
        $this->assertSame('Titular vigente', $payments[0]->cliente_titular);
        $this->assertSame('Titular vigente', Seguimiento::with('cliente')->firstOrFail()->cliente->nombre);
    }

    public function test_import_rejects_invalid_process_dates_and_lost_account_precision(): void
    {
        $this->actingAs(User::factory()->administrator()->create());
        $headers = 'cartera,documento,nombre,codigo,entidad,cosecha,producto,sub_producto,departamento,deuda_total,deuda_capital,campana,FEC_PRO';

        $this->postJson('/cargas/data', ['file' => $this->csv('bad-date.csv', [$headers, 'C,123,Nombre,001234,E,2026,P,S,Lima,1,1,C,20261301'])])
            ->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->postJson('/cargas/data', ['file' => $this->csv('lost-code.csv', [$headers, 'C,123,Nombre,1.10814E+17,E,2026,P,S,Lima,1,1,C,20261101'])])
            ->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->assertDatabaseCount('Cartera_Mensual', 0);
    }

    private function csv(string $name, array $lines): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, implode("\n", $lines));
    }
}
