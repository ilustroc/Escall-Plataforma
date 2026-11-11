<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\LegacyFixtures;
use Tests\TestCase;

class BladeInertiaNavigationTest extends TestCase
{
    use RefreshDatabase, LegacyFixtures;

    public function test_dashboard_is_a_standard_blade_document(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/inicio')
            ->assertOk()
            ->assertViewIs('dashboard.index')
            ->assertHeaderMissing('X-Inertia')
            ->assertSee('Generaciones realizadas');
    }

    public function test_blade_routes_return_an_inertia_location_when_an_old_inertia_visit_reaches_them(): void
    {
        $admin = User::factory()->administrator()->create();

        $this->actingAs($admin)
            ->get('/inicio', ['X-Inertia' => 'true'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', route('dashboard'));

        $this->get('/cargas/asignacion', ['X-Inertia' => 'true'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', route('cargas.asignacion'));
    }

    public function test_reports_remain_an_inertia_response_after_a_blade_navigation(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/reportes', ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'Reportes/Index');
    }

    public function test_administrator_can_open_blade_imports_while_advisor_cannot(): void
    {
        $admin = User::factory()->administrator()->create();
        $advisor = User::factory()->create();

        $this->actingAs($admin)
            ->get('/cargas')
            ->assertOk()
            ->assertViewIs('cargas.index')
            ->assertSee('Integración');

        $this->actingAs($advisor)
            ->get('/cargas')
            ->assertForbidden();

        $this->get('/admin')
            ->assertForbidden();
    }

    public function test_vue_layout_uses_native_links_for_blade_destinations(): void
    {
        $layout = file_get_contents(resource_path('js/Layouts/AppLayout.vue'));

        $this->assertStringContainsString('const isBladeDestination', $layout);
        $this->assertStringContainsString('function visitBlade', $layout);
        $this->assertStringContainsString('window.location.assign(href)', $layout);
        $this->assertStringContainsString('navigation.filter(item => isBladeDestination(item.href))', $layout);
        $this->assertStringContainsString('navigation.filter(item => !isBladeDestination(item.href))', $layout);
        $this->assertStringContainsString('<a href="/inicio"', $layout);
        $this->assertStringNotContainsString('<Link href="/inicio"', $layout);
    }

    public function test_current_cartera_fields_are_exposed_to_reports_and_client_detail(): void
    {
        $client = $this->client([
            'documento' => '70123456',
            'nombre' => 'Cliente con campos vigentes',
        ]);
        $user = User::factory()->create();
        $this->document($user, $client);

        $this->actingAs($user)
            ->get('/clientes?dni=70123456', ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertHeader('X-Inertia', 'true')
            ->assertSee('Cliente con campos vigentes')
            ->assertSee('70123456');

        $this->get('/reportes', ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('props.documents.data.0.cliente.nombre', 'Cliente con campos vigentes')
            ->assertJsonPath('props.documents.data.0.cliente.documento', '70123456');
    }
}
