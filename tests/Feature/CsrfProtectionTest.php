<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CsrfProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['security.enforce_csrf' => true]);
    }

    public static function mutationEndpoints(): array
    {
        return array_map(fn ($url) => [$url], [
            '/login', '/logout', '/cargas/data', '/cargas/pagos', '/cargas/seguimientos', '/cargas/evolutivo',
            '/clientes/generar-word', '/reportes/eliminar', '/propuestas', '/propuestas/eliminar',
            '/admin/crear', '/admin/actualizar', '/admin/toggle', '/admin/eliminar',
            '/api/seguimientos', '/api/seguimientos/eliminar',
        ]);
    }

    #[DataProvider('mutationEndpoints')]
    public function test_every_state_changing_route_rejects_missing_csrf_token(string $url): void
    {
        if ($url !== '/login') {
            $this->actingAs(User::factory()->administrator()->create());
        }
        $this->csrf();
        $this->postJson($url)->assertStatus(419);
    }

    public function test_incorrect_token_is_rejected_and_correct_token_allows_login_and_logout(): void
    {
        $user = User::factory()->create();
        $token = $this->csrf();
        $payload = ['usuario' => $user->usuario, 'contrasena' => 'Testing-only-password-42!'];
        $this->post('/login', $payload + ['_token' => 'forged-token'])->assertStatus(419);
        $this->assertGuest();

        $this->post('/login', $payload + ['_token' => $token])->assertRedirect('/inicio');
        $this->assertAuthenticatedAs($user);
        $this->post('/logout', ['_token' => session()->token()])->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_json_api_accepts_a_matching_csrf_header_but_not_a_foreign_token(): void
    {
        $this->actingAs(User::factory()->create());
        $token = $this->csrf();
        $this->postJson('/api/seguimientos', [], ['X-CSRF-TOKEN' => 'foreign'])->assertStatus(419);
        // Empty input reaches validation only after successful CSRF verification.
        $this->postJson('/api/seguimientos', [], ['X-CSRF-TOKEN' => $token])->assertUnprocessable();
    }
}
