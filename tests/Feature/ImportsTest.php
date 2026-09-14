<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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

    public function test_administrator_can_import_legacy_data_and_payments_from_utf8_csv(): void
    {
        $this->actingAs(User::factory()->administrator()->create());
        $this->postJson('/cargas/data', ['file' => $this->csv('data.csv', [
            'cartera,dni,titular,codigo,entidad,cosecha,producto,sub_producto,historico,departamento,rango,deuda_total,deuda_capital,campana',
            'Cartera,12345678,Cliente,C-100,Entidad,2026,Tarjeta,Classic,,Lima,1,"1,500.50","1,000.00",Pruebas',
        ])])->assertSuccessful()->assertJsonPath('success', true);
        $this->assertDatabaseHas('cartera_clientes', ['codigo' => 'C-100', 'dni' => '12345678']);

        $this->postJson('/cargas/pagos', ['file' => $this->csv('pagos.csv', [
            'codigo,fecha,monto,asesor,operacion,observacion',
            'C-100,2026-09-13,100.50,Asesor,OP-1,Pago de prueba',
        ])])->assertSuccessful()->assertJsonPath('success', true);
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
        $this->postJson('/cargas/data', ['file' => UploadedFile::fake()->createWithContent('data.xlsx', '{"not":"a workbook"}')])
            ->assertUnprocessable()->assertJsonValidationErrors('file');
        $this->assertDatabaseCount('cartera_clientes', 0);
    }

    public function test_upload_rejects_file_size_and_row_limits(): void
    {
        $this->actingAs(User::factory()->administrator()->create());
        $tooLarge = UploadedFile::fake()->createWithContent('data.csv', str_repeat('a', 10 * 1024 * 1024 + 1));
        $this->postJson('/cargas/data', ['file' => $tooLarge])->assertUnprocessable()->assertJsonValidationErrors('file');

        $rows = ['cartera,dni,titular,codigo,entidad,cosecha,producto,sub_producto,historico,departamento,rango,deuda_total,deuda_capital,campana'];
        for ($i = 1; $i <= 10001; $i++) $rows[] = "C,{$i},Cliente,C-{$i},E,2026,P,S,,L,1,1,1,C";
        $this->postJson('/cargas/data', ['file' => $this->csv('many.csv', $rows)])
            ->assertUnprocessable()->assertJsonValidationErrors('file');
    }

    private function csv(string $name, array $lines): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, implode("\n", $lines));
    }
}
