<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\LegacyFixtures;
use Tests\TestCase;

class ClientDashboardTest extends TestCase
{
    use RefreshDatabase, LegacyFixtures;

    public function test_dashboard_and_client_pages_are_inertia_pages_with_account_data(): void
    {
        $this->client();
        $this->client(['codigo' => 'TEST-ACCOUNT-002', 'deuda_total' => 1000]);
        $user = User::factory()->create();
        $this->actingAs($user);
        $this->get('/inicio', ['X-Inertia' => 'true'])->assertOk()->assertHeader('X-Inertia', 'true');
        $this->get('/inicio?dni=12345678', ['X-Inertia' => 'true'])->assertOk()
            ->assertSee('12345678')->assertSee('TEST-ACCOUNT-001')->assertSee('TEST-ACCOUNT-002');
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

    public function test_unknown_client_is_not_found_and_client_search_empty_state_is_valid(): void
    {
        $this->actingAs(User::factory()->create());
        $this->get('/clientes?dni=99999999')->assertNotFound();
        $this->getJson('/api/clientes?dni=99999999')->assertOk()->assertJsonCount(0, 'data');
    }
}
