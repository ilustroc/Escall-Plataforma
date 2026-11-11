<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\LegacyFixtures;
use Tests\TestCase;

class ClientDashboardTest extends TestCase
{
    use RefreshDatabase, LegacyFixtures;

    public function test_dashboard_is_a_blade_page_and_client_detail_remains_an_inertia_page(): void
    {
        $this->client();
        $this->client(['codigo' => 'TEST-ACCOUNT-002', 'deuda_total' => 1000]);
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->get('/inicio')->assertOk()
            ->assertViewIs('dashboard.index')
            ->assertSee('Generaciones realizadas')
            ->assertDontSee('Buscar cartera')
            ->assertDontSee('resources/js/app.js', false);
        $this->get('/clientes?dni=12345678', ['X-Inertia' => 'true'])->assertOk()
            ->assertHeader('X-Inertia', 'true')->assertSee('TEST-ACCOUNT-001')->assertSee('TEST-ACCOUNT-002');
    }

    public function test_client_api_applies_legacy_combined_filters_without_sql_injection(): void
    {
        $this->client();
        $this->client(['dni' => '87654321', 'codigo' => 'OTHER-ACCOUNT', 'titular' => 'Otra persona', 'cartera' => 'Otra cartera']);
        $this->actingAs(User::factory()->create());

        $this->getJson('/api/clientes?dni=1234&codigo=ACCOUNT&titular=pruebas&cartera='.urlencode('Cartera de pruebas'))
            ->assertOk()->assertJsonPath('ok', true)->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.codigo', 'TEST-ACCOUNT-001');
        $this->getJson('/api/clientes?dni='.urlencode("' OR 1=1 --"))->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_client_suggestions_are_private_limited_and_support_partial_matches(): void
    {
        $this->client(['dni' => '80179408', 'codigo' => '12320207', 'titular' => 'Estacio Infante Maria']);
        $this->client(['dni' => '00517123', 'codigo' => '13377825', 'titular' => 'Quispe Fraquita Judith']);
        for ($index = 0; $index < 12; $index++) {
            $this->client(['dni' => "1230000{$index}", 'codigo' => "OTHER-{$index}", 'titular' => "Cliente parcial {$index}"]);
        }

        $this->getJson('/api/clientes/sugerencias?q=123')->assertUnauthorized();

        $this->actingAs(User::factory()->create())
            ->getJson('/api/clientes/sugerencias?q=123')
            ->assertOk()
            ->assertJsonCount(10, 'data');

        $this->getJson('/api/clientes/sugerencias?q=123202')
            ->assertOk()->assertJsonFragment(['codigo' => '12320207']);

        $this->getJson('/api/clientes/sugerencias?q='.urlencode("' OR 1=1 --"))
            ->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_unknown_client_is_not_found_and_client_search_empty_state_is_valid(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/clientes?dni=99999999')->assertNotFound();
        $this->getJson('/api/clientes?dni=99999999')->assertOk()->assertJsonCount(0, 'data');
    }
}
