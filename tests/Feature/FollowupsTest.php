<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\LegacyFixtures;
use Tests\TestCase;

class FollowupsTest extends TestCase
{
    use RefreshDatabase, LegacyFixtures;

    public function test_user_can_create_list_and_delete_their_own_followup(): void
    {
        $this->client();
        $user = User::factory()->create();
        $this->actingAs($user);
        $response = $this->postJson('/api/seguimientos', [
            'codigo' => 'TEST-ACCOUNT-001', 'fecha' => now()->toDateString(), 'hora' => '15:30',
            'tipo' => 'recordatorio', 'descripcion' => 'Llamar al cliente', 'estado' => 'pendiente',
            'usuario_id' => 99999,
        ])->assertSuccessful()->assertJsonPath('success', true);
        $id = $response->json('id');
        $this->assertDatabaseHas('seguimientos', ['id' => $id, 'usuario_id' => $user->id, 'codigo' => 'TEST-ACCOUNT-001']);
        $this->getJson('/api/seguimientos')->assertOk()->assertJsonFragment(['id' => $id, 'descripcion' => 'Llamar al cliente']);
        $this->postJson('/api/seguimientos/eliminar', ['id' => $id])->assertSuccessful();
        $this->assertDatabaseMissing('seguimientos', ['id' => $id]);
    }

    public function test_other_users_events_are_private_even_with_todos_query_and_cannot_be_deleted(): void
    {
        $this->client();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $id = $this->followup($owner, ['descripcion' => 'PRIVATE-OTHER-USER']);
        $this->actingAs($other)->getJson('/api/seguimientos?todos=1')->assertOk()->assertDontSee('PRIVATE-OTHER-USER');
        $this->postJson('/api/seguimientos/eliminar', ['id' => $id])->assertForbidden();
        $this->assertDatabaseHas('seguimientos', ['id' => $id]);

        $this->actingAs(User::factory()->administrator()->create())->getJson('/api/seguimientos?todos=1')
            ->assertOk()->assertSee('PRIVATE-OTHER-USER');
        $this->postJson('/api/seguimientos/eliminar', ['id' => $id])->assertSuccessful();
    }

    public function test_followup_validation_rejects_invalid_date_hour_and_type(): void
    {
        $this->actingAs(User::factory()->create())->postJson('/api/seguimientos', [
            'codigo' => 'missing-account', 'fecha' => '2026-99-99', 'hora' => '25:70', 'tipo' => 'admin',
        ])->assertUnprocessable()->assertJsonValidationErrors(['fecha', 'hora', 'tipo']);
        $this->assertDatabaseCount('seguimientos', 0);
    }

    public function test_evolution_api_respects_user_scope_and_administrator_override(): void
    {
        $owner = User::factory()->create();
        DB::table('seguimientos_evolutivo')->insert([
            'usuario_id' => $owner->id, 'dni' => '12345678', 'tipo_acuerdo' => 'convenio',
            'fecha' => now()->toDateString(), 'monto' => 500, 'estado' => 'pendiente', 'gestion' => 'PRIVATE-EVOLUTION',
        ]);
        $this->actingAs($owner)->getJson('/api/evolutivo')->assertOk()->assertSee('PRIVATE-EVOLUTION');
        $this->actingAs(User::factory()->create())->getJson('/api/evolutivo?todos=1')->assertOk()->assertDontSee('PRIVATE-EVOLUTION');
        $this->actingAs(User::factory()->administrator()->create())->getJson('/api/evolutivo?todos=1')->assertOk()->assertSee('PRIVATE-EVOLUTION');
    }
}
