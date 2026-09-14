<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\LegacyFixtures;
use Tests\TestCase;

class WordDocumentsTest extends TestCase
{
    use RefreshDatabase, LegacyFixtures;

    public static function supportedDocuments(): array
    {
        $cases = [];
        foreach (range(1, 5) as $accounts) {
            $cases["convenio {$accounts}"] = ['convenio', false, $accounts];
            $cases["cancelacion {$accounts}"] = ['cancelacion', false, $accounts];
            $cases["cancelacion cuotas {$accounts}"] = ['cancelacion', true, $accounts];
        }

        return $cases;
    }

    #[DataProvider('supportedDocuments')]
    public function test_real_word_templates_generate_private_downloadable_documents(string $type, bool $installments, int $accountCount): void
    {
        $client = $this->client();
        $accounts = ['TEST-ACCOUNT-001'];
        for ($i = 2; $i <= $accountCount; $i++) {
            $accounts[] = $code = 'TEST-ACCOUNT-00'.$i;
            $this->client(['codigo' => $code]);
        }
        config(['platform.bank_accounts' => ['bcp' => ['label' => 'Banco de pruebas', 'value' => 'TEST-ACCOUNT-NOT-REAL']]]);
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->postJson('/clientes/generar-word', [
            'cliente_id' => $client, 'tipo_documento' => $type, 'cuentas_seleccionadas' => $accounts,
            'tipo_facilidad' => 'Convenio de prueba', 'fecha_solicitud' => '2026-09-13', 'cuenta_banco' => 'bcp',
            'usar_cuotas' => $installments, 'numero_cuotas' => 5, 'cuota_inicial' => 100,
            'monto_convenio' => 500, 'primera_cuota' => 100, 'f_cuota' => '2026-10-01',
            'cancelacion' => 500, 'fecha_pago' => '2026-10-01', 'cuota_1' => 250, 'cuota_2' => 250,
            'fecha_cuota_1' => '2026-10-01', 'fecha_cuota_2' => '2026-11-01',
            'nombre' => 'ATTACKER-SUPPLIED-NAME', 'deuda_total' => 1, 'usuario_id' => 99999,
        ])->assertSuccessful()->assertJsonPath('success', true);
        $document = DB::table('documentos')->first();
        $this->assertNotNull($document);
        $this->assertSame($user->id, $document->usuario_id);
        $this->assertSame($client, $document->cliente_id);
        $this->assertStringStartsWith('PK', $document->contenido);
        $download = $this->get($response->json('download_url'))->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        $this->assertSame($document->contenido, $download->getContent());

        $path = tempnam(sys_get_temp_dir(), 'escall-doc-test-');
        file_put_contents($path, $document->contenido);
        try {
            $zip = new \ZipArchive();
            $this->assertTrue($zip->open($path) === true);
            $xml = $zip->getFromName('word/document.xml');
            $zip->close();
            $this->assertIsString($xml);
            $this->assertStringNotContainsString('ATTACKER-SUPPLIED-NAME', $xml);
            $this->assertStringContainsString('Cliente de pruebas', strip_tags($xml));
            $this->assertStringNotContainsString('${', $xml);
        } finally {
            unlink($path);
        }
    }

    public function test_word_requires_configured_bank_and_valid_balanced_installments(): void
    {
        $client = $this->client();
        config(['platform.bank_accounts' => []]);
        $this->actingAs(User::factory()->create())->postJson('/clientes/generar-word', [
            'cliente_id' => $client, 'tipo_documento' => 'cancelacion', 'cuentas_seleccionadas' => ['TEST-ACCOUNT-001'],
            'tipo_facilidad' => 'Cancelación', 'fecha_solicitud' => '2026-09-13', 'cuenta_banco' => '../../.env',
            'usar_cuotas' => true, 'cancelacion' => 500, 'fecha_pago' => '2026-10-01',
            'cuota_1' => 200, 'cuota_2' => 200, 'fecha_cuota_1' => '2026-10-01', 'fecha_cuota_2' => '2026-11-01',
        ])->assertUnprocessable()->assertJsonValidationErrors(['cuenta_banco', 'cuota_2']);
        $this->assertDatabaseCount('documentos', 0);
    }
}
