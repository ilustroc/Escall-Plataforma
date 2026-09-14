<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\LegacyFixtures;
use Tests\TestCase;

class ProposalsAndReportsTest extends TestCase
{
    use RefreshDatabase, LegacyFixtures;

    public function test_proposal_creation_preserves_amounts_accounts_sustento_and_excel_export(): void
    {
        $client = $this->client();
        $this->client(['codigo' => 'TEST-ACCOUNT-002']);
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->get('/propuestas?dni=12345678', ['X-Inertia' => 'true'])->assertOk()->assertSee('TEST-ACCOUNT-001');
        $response = $this->postJson('/propuestas', [
            'cliente_id' => $client, 'fecha_pago' => '2026-09-30', 'observacion' => 'Observación de prueba',
            'sustento' => 'Sustento de prueba', 'cuentas_seleccionadas' => ['TEST-ACCOUNT-001', 'TEST-ACCOUNT-002'],
            'propuesta_por_cuenta' => ['TEST-ACCOUNT-001' => 450.25, 'TEST-ACCOUNT-002' => 50.75],
            'usuario_id' => 99999, 'propuesta_cliente' => 1,
        ])->assertSuccessful()->assertJsonPath('success', true);
        $id = $response->json('id');
        $this->assertDatabaseHas('propuestas', ['id' => $id, 'usuario_id' => $user->id, 'propuesta_cliente' => 501]);
        $proposal = DB::table('propuestas')->find($id);
        $this->assertStringContainsString('Sustento: Sustento de prueba', $proposal->observacion);
        $this->assertCount(2, json_decode($proposal->cuentas_seleccionadas, true));

        $export = $this->get('/propuestas/excel?propuesta_id='.$id)->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('Content-Disposition');
        $this->assertStringStartsWith('PK', $export->getContent());
        $this->assertStringContainsString('attachment', $export->headers->get('Content-Disposition'));
    }

    public function test_proposal_rejects_accounts_of_other_clients_and_negative_amounts(): void
    {
        $client = $this->client();
        $this->client(['codigo' => 'FOREIGN-ACCOUNT', 'dni' => '87654321']);
        $this->actingAs(User::factory()->create());
        $this->postJson('/propuestas', [
            'cliente_id' => $client, 'fecha_pago' => '2026-09-30',
            'cuentas_seleccionadas' => ['FOREIGN-ACCOUNT'], 'propuesta_por_cuenta' => ['FOREIGN-ACCOUNT' => 100],
        ])->assertUnprocessable();
        $this->postJson('/propuestas', [
            'cliente_id' => $client, 'fecha_pago' => '2026-09-30',
            'cuentas_seleccionadas' => ['TEST-ACCOUNT-001'], 'propuesta_por_cuenta' => ['TEST-ACCOUNT-001' => -100],
        ])->assertUnprocessable();
        $this->assertDatabaseCount('propuestas', 0);
    }

    public function test_report_document_download_and_deletion_enforce_ownership(): void
    {
        $client = $this->client();
        $owner = User::factory()->create();
        $id = $this->document($owner, $client);
        $this->actingAs(User::factory()->create())->get('/reportes/descargar?id='.$id)->assertForbidden();
        $this->post('/reportes/eliminar', ['id' => $id])->assertForbidden();
        $this->assertDatabaseHas('documentos', ['id' => $id]);

        $this->actingAs($owner)->get('/reportes/descargar?id='.$id)->assertOk()
            ->assertContent('private-document-fixture-bytes')->assertHeader('Content-Disposition');
        $this->post('/reportes/eliminar', ['id' => $id])->assertRedirect();
        $this->assertDatabaseMissing('documentos', ['id' => $id]);
    }

    public function test_proposal_exports_and_deletion_enforce_ownership_and_allow_administrators(): void
    {
        $client = $this->client();
        $owner = User::factory()->create();
        $id = $this->proposal($owner, $client);
        $this->actingAs(User::factory()->create())->get('/propuestas/excel?propuesta_id='.$id)->assertForbidden();
        $this->post('/propuestas/eliminar', ['id' => $id])->assertForbidden();
        $this->assertDatabaseHas('propuestas', ['id' => $id]);

        $this->actingAs(User::factory()->administrator()->create())->get('/propuestas/excel?propuesta_id='.$id)->assertOk();
        $this->post('/propuestas/eliminar', ['id' => $id])->assertRedirect();
        $this->assertDatabaseMissing('propuestas', ['id' => $id]);
    }

    public function test_report_tabs_are_private_paginated_and_honor_dates(): void
    {
        $client = $this->client();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->document($other, $client, ['nombre_archivo' => 'PRIVATE-OTHER-OWNER.docx']);
        $this->document($owner, $client, ['nombre_archivo' => 'OLD-RECORD.docx', 'creado_en' => '2020-01-01 10:00:00']);
        for ($i = 0; $i < 12; $i++) {
            $this->document($owner, $client, ['nombre_archivo' => "report-{$i}.docx", 'creado_en' => '2026-09-13 12:00:00']);
        }
        $this->proposal($owner, $client);
        $this->actingAs($owner)->get('/reportes?tab=acuerdos&desde=2026-09-01&hasta=2026-09-30', ['X-Inertia' => 'true'])
            ->assertOk()->assertHeader('X-Inertia', 'true')->assertDontSee('PRIVATE-OTHER-OWNER')->assertDontSee('OLD-RECORD');
        $this->get('/reportes?tab=propuestas', ['X-Inertia' => 'true'])->assertOk()->assertHeader('X-Inertia', 'true');
    }

    public function test_document_download_does_not_accept_a_filesystem_path_as_identifier(): void
    {
        $this->actingAs(User::factory()->create())->getJson('/reportes/descargar?id=..%2F..%2F.env')->assertUnprocessable();
    }
}
