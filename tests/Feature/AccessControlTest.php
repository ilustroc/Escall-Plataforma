<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public static function privateRoutes(): array
    {
        return [
            'dashboard' => ['GET', '/inicio'],
            'upload screen' => ['GET', '/cargas'],
            'private import template' => ['GET', '/cargas/plantilla'],
            'portfolio import' => ['POST', '/cargas/data'],
            'payments import' => ['POST', '/cargas/pagos'],
            'followup import' => ['POST', '/cargas/seguimientos'],
            'evolution import' => ['POST', '/cargas/evolutivo'],
            'client screen' => ['GET', '/clientes'],
            'client search api' => ['GET', '/api/clientes'],
            'word generation' => ['POST', '/clientes/generar-word'],
            'report screen' => ['GET', '/reportes'],
            'document download' => ['GET', '/reportes/descargar?id=1'],
            'document deletion' => ['POST', '/reportes/eliminar'],
            'proposal screen' => ['GET', '/propuestas?dni=12345678'],
            'proposal creation' => ['POST', '/propuestas'],
            'proposal export' => ['GET', '/propuestas/excel?propuesta_id=1'],
            'proposal deletion' => ['POST', '/propuestas/eliminar'],
            'admin screen' => ['GET', '/admin'],
            'user creation' => ['POST', '/admin/crear'],
            'user update' => ['POST', '/admin/actualizar'],
            'user status' => ['POST', '/admin/toggle'],
            'user deletion' => ['POST', '/admin/eliminar'],
            'followup screen' => ['GET', '/seguimientos'],
            'followup api' => ['GET', '/api/seguimientos'],
            'followup creation' => ['POST', '/api/seguimientos'],
            'followup deletion' => ['POST', '/api/seguimientos/eliminar'],
            'evolution api' => ['GET', '/api/evolutivo'],
            'logout' => ['POST', '/logout'],
        ];
    }

    #[DataProvider('privateRoutes')]
    public function test_every_private_endpoint_requires_authentication(string $method, string $url): void
    {
        $this->json($method, $url)->assertUnauthorized();
    }

    public static function administrationRoutes(): array
    {
        $routes = [];
        foreach (['asesor', 'supervisor'] as $role) {
            foreach ([
                ['GET', '/admin'], ['POST', '/admin/crear'], ['POST', '/admin/actualizar'], ['POST', '/admin/toggle'], ['POST', '/admin/eliminar'],
                ['GET', '/cargas'], ['GET', '/cargas/asignacion'], ['GET', '/cargas/pagos'], ['GET', '/cargas/ccd'], ['GET', '/cargas/data'],
                ['POST', '/cargas/asignacion'], ['POST', '/cargas/data'], ['POST', '/cargas/pagos'],
            ] as [$method, $url]) {
                $routes[$role.' '.$url] = [$role, $method, $url];
            }
        }

        return $routes;
    }

    #[DataProvider('administrationRoutes')]
    public function test_changing_the_url_does_not_grant_administrative_permissions(string $role, string $method, string $url): void
    {
        $this->actingAs(User::factory()->create(['rol' => $role]))->json($method, $url)->assertForbidden();
    }

    public function test_inactive_users_cannot_keep_using_an_existing_session(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $user->update(['activo' => 0]);

        $response = $this->getJson('/api/clientes?q=test');
        $this->assertContains($response->status(), [401, 403]);
        $this->assertGuest();
    }

    public function test_removed_debug_route_is_not_served(): void
    {
        $this->actingAs(User::factory()->administrator()->create())->get('/vista')->assertNotFound();
    }
}
