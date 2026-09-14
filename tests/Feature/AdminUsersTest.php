<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_create_update_toggle_and_delete_a_user_with_secure_hashing(): void
    {
        $this->actingAs(User::factory()->administrator()->create());
        $this->post('/admin/crear', [
            'nombre' => 'Persona de prueba', 'usuario' => 'created@example.test', 'rol' => 'asesor',
            'contrasena' => 'A-valid-testing-password-42!', 'activo' => 1,
        ])->assertRedirect();
        $created = User::where('usuario', 'created@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('A-valid-testing-password-42!', $created->contrasena));
        $originalHash = $created->contrasena;

        $this->post('/admin/actualizar', [
            'id' => $created->id, 'nombre' => 'Nombre actualizado', 'usuario' => $created->usuario,
            'rol' => 'supervisor', 'activo' => 1, 'contrasena' => '',
        ])->assertRedirect();
        $this->assertDatabaseHas('usuarios', ['id' => $created->id, 'nombre' => 'Nombre actualizado', 'rol' => 'supervisor']);
        $this->assertSame($originalHash, $created->fresh()->contrasena);

        $this->post('/admin/toggle', ['id' => $created->id])->assertRedirect();
        $this->assertDatabaseHas('usuarios', ['id' => $created->id, 'activo' => 0]);
        $this->post('/admin/toggle', ['id' => $created->id])->assertRedirect();
        $this->assertDatabaseHas('usuarios', ['id' => $created->id, 'activo' => 1]);

        $this->post('/admin/eliminar', ['id' => $created->id])->assertRedirect();
        $this->assertDatabaseMissing('usuarios', ['id' => $created->id]);
    }

    public function test_administrator_cannot_deactivate_or_delete_their_own_account(): void
    {
        $user = User::factory()->administrator()->create();
        $this->actingAs($user);
        $this->postJson('/admin/toggle', ['id' => $user->id])->assertUnprocessable();
        $this->postJson('/admin/eliminar', ['id' => $user->id])->assertUnprocessable();
        $this->assertDatabaseHas('usuarios', ['id' => $user->id, 'activo' => 1]);
    }

    public function test_user_validation_rejects_unknown_roles_duplicate_identifiers_and_weak_passwords(): void
    {
        $user = User::factory()->administrator()->create();
        $this->actingAs($user);
        $this->postJson('/admin/crear', [
            'nombre' => 'Invalid', 'usuario' => $user->usuario, 'rol' => 'super-admin',
            'contrasena' => '123', 'activo' => 1,
        ])->assertUnprocessable()->assertJsonValidationErrors(['usuario', 'rol', 'contrasena']);
        $this->assertDatabaseCount('usuarios', 1);
    }

    public function test_inertia_does_not_expose_password_hashes_in_admin_or_shared_user_props(): void
    {
        $user = User::factory()->administrator()->create();
        $response = $this->actingAs($user)->get('/admin', ['X-Inertia' => 'true'])->assertOk();
        $this->assertStringNotContainsString($user->contrasena, $response->getContent());
        $this->assertStringNotContainsString('"contrasena"', $response->getContent());
    }
}
