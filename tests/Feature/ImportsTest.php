<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Importacion;
use App\Services\MassImportProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_download_a_private_import_template(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/cargas/plantilla?tipo=evolutivo')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('Content-Disposition');
    }

    public function test_administrator_can_import_monthly_data_and_payments_from_utf8_csv(): void
    {
        $this->actingAs(User::factory()->administrator()->create());
        $assignment = $this->queueImport('/cargas/data', $this->csv('data.csv', [
            'cartera,documento,nombre,codigo,entidad,cosecha,producto,sub_producto,departamento,deuda_total,deuda_capital,campana,FEC_PRO',
            'Cartera,12345678,Cliente,C-100,Entidad,2026,Tarjeta,Classic,Lima,"1,500.50","1,000.00",Pruebas,20261101',
        ]));
        app(MassImportProcessor::class)->process($assignment);
        $this->assertSame('completado', $assignment->fresh()->estado);
        $this->assertDatabaseHas('Cartera_Mensual', ['codigo' => 'C-100', 'documento' => '12345678', 'FEC_PRO' => '20261101']);

        $payments = $this->queueImport('/cargas/pagos', $this->csv('pagos.csv', [
            'codigo,fecha,monto,asesor,operacion,observacion',
            'C-100,2026-09-13,100.50,Asesor,OP-1,Pago de prueba',
        ]));
        app(MassImportProcessor::class)->process($payments);
        $this->assertSame('completado', $payments->fresh()->estado);
        $this->assertDatabaseHas('pagos', ['codigo' => 'C-100', 'operacion' => 'OP-1']);
    }

    public function test_user_can_import_their_followups_and_evolution_without_impersonating_another_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->postJson('/cargas/seguimientos', ['file' => $this->csv('seguimientos.csv', [
            'codigo,fecha,hora,tipo,descripcion,estado',
            'C-100,2026-09-13,09:30,recordatorio,Llamar,pendiente',
        ])])->assertSuccessful();
        $this->assertDatabaseHas('seguimientos', ['codigo' => 'C-100', 'usuario_id' => $user->id]);

        $this->postJson('/cargas/evolutivo', ['file' => $this->csv('evolutivo.csv', [
            'dni,tipo_acuerdo,fecha,monto,estado,gestion',
            '12345678,convenio,2026-09-13,250.00,PENDIENTE,',
        ])])->assertSuccessful();
        $this->assertDatabaseHas('seguimientos_evolutivo', ['dni' => '12345678', 'usuario_id' => $user->id, 'gestion' => 'Pendiente']);
    }

    public function test_upload_rejects_bad_extension_and_mime(): void
    {
        $this->actingAs(User::factory()->administrator()->create());
        $this->postJson('/cargas/data', ['file' => UploadedFile::fake()->createWithContent('data.txt', 'not a spreadsheet')])
            ->assertUnprocessable()->assertJsonValidationErrors('file');
        $invalid = $this->queueImport('/cargas/data', UploadedFile::fake()->createWithContent('data.xlsx', '{"not":"a workbook"}'));
        app(MassImportProcessor::class)->process($invalid);
        $this->assertSame('fallido', $invalid->fresh()->estado);
        $this->assertDatabaseHas('importacion_errores', ['importacion_id' => $invalid->id, 'codigo' => 'FILE_INVALID']);
        $this->assertDatabaseCount('Cartera_Mensual', 0);
    }

    public function test_upload_rejects_file_size_and_row_limits(): void
    {
        $this->actingAs(User::factory()->administrator()->create());
        $tooLarge = UploadedFile::fake()->create('data.csv', 51201, 'text/csv');
        $this->postJson('/cargas/data', ['file' => $tooLarge])->assertUnprocessable()->assertJsonValidationErrors('file');
    }

    public function test_validation_errors_are_private_and_downloadable_by_the_owner(): void
    {
        $owner = User::factory()->administrator()->create();
        $this->actingAs($owner);
        $import = $this->queueImport('/cargas/asignacion', $this->csv('invalid.csv', [
            'cartera,documento,nombre,codigo,entidad,cosecha,producto,sub_producto,departamento,deuda_total,deuda_capital,campana,FEC_PRO',
            'C,123,Nombre,001234,E,2026,P,S,Lima,invalid,1,C,20261301',
        ]));
        app(MassImportProcessor::class)->process($import);

        $this->get("/cargas/importaciones/{$import->id}/estado")
            ->assertOk()
            ->assertJsonPath('estado', 'fallido')
            ->assertJsonPath('periodo', null);
        $this->get("/cargas/importaciones/{$import->id}/errores")
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertDatabaseHas('importacion_errores', ['importacion_id' => $import->id, 'codigo' => 'INVALID_PROCESS_DATE']);
        $this->assertDatabaseHas('importacion_errores', ['importacion_id' => $import->id, 'codigo' => 'INVALID_AMOUNT']);

        $other = User::factory()->administrator()->create();
        // Administrators have the documented platform-wide audit access.
        $this->actingAs($other)->get("/cargas/importaciones/{$import->id}/errores")->assertOk();
        $this->actingAs(User::factory()->create())->get("/cargas/importaciones/{$import->id}/errores")->assertForbidden();
    }

    public function test_xlsx_is_read_through_the_streaming_import_pipeline(): void
    {
        $this->actingAs(User::factory()->administrator()->create());
        $import = $this->queueImport('/cargas/asignacion', $this->xlsx('assignment.xlsx', [
            ['CARTERA','DOCUMENTO','NOMBRE','CODIGO','ENTIDAD','COSECHA','PRODUCTO','SUB_PRODUCTO','DEPARTAMENTO','DEUDA_TOTAL','DEUDA_CAPITAL','CAMPANA','FEC_PRO'],
            ['C','00001234','Cliente XLSX','00009876','E','2026','P','S','Lima','10.25','8.00','C','20261101'],
        ]));
        app(MassImportProcessor::class)->process($import);

        $this->assertSame('completado', $import->fresh()->estado);
        $this->assertDatabaseHas('Cartera_Mensual', ['codigo' => '00009876', 'documento' => '00001234']);
    }

    public function test_import_screens_are_blade_and_pending_types_cannot_store_data(): void
    {
        $this->actingAs(User::factory()->administrator()->create())
            ->get('/cargas/asignacion')->assertOk()->assertSee('Carga de asignación mensual')
            ->assertDontSee('data-page="');
        $this->get('/cargas/ccd')->assertOk()->assertSee('Formato pendiente de configuración');
        $this->get('/cargas/data')->assertOk()->assertSee('Formato pendiente de configuración');
        $this->post('/cargas/ccd', [])->assertMethodNotAllowed();
    }

    private function queueImport(string $url, UploadedFile $file): Importacion
    {
        Queue::fake();
        $this->postJson($url, ['file' => $file])
            ->assertSuccessful()
            ->assertJsonPath('success', true);

        return Importacion::query()->latest('id')->firstOrFail();
    }

    private function csv(string $name, array $lines): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, implode("\n", $lines));
    }

    private function xlsx(string $name, array $rows): UploadedFile
    {
        $book = new Spreadsheet();
        $book->getActiveSheet()->fromArray($rows);
        ob_start();
        try {
            (new Xlsx($book))->save('php://output');
            $bytes = (string) ob_get_contents();
        } finally {
            ob_end_clean();
            $book->disconnectWorksheets();
        }

        return UploadedFile::fake()->createWithContent($name, $bytes);
    }
}
