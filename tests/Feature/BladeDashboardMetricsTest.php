<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\LegacyFixtures;
use Tests\TestCase;

class BladeDashboardMetricsTest extends TestCase
{
    use RefreshDatabase, LegacyFixtures;

    public function test_generation_metric_is_scoped_to_the_authenticated_user(): void
    {
        $client = $this->client();
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $this->document($owner, $client);
        $this->proposal($owner, $client);
        $this->document($other, $client);

        $this->actingAs($owner)->get('/inicio')
            ->assertViewHas('generationStats', fn (array $stats) => $stats === ['documents' => 1, 'proposals' => 1, 'total' => 2]);

        $this->actingAs(User::factory()->administrator()->create())->get('/inicio')
            ->assertViewHas('generationStats', fn (array $stats) => $stats === ['documents' => 2, 'proposals' => 1, 'total' => 3]);
    }

    public function test_recent_payments_are_scoped_and_sorted_by_date_then_id(): void
    {
        $this->client(['codigo' => 'PAY-OWNER', 'dni' => '12345678', 'titular' => 'Cliente titular']);
        $owner = User::factory()->create();
        $other = User::factory()->create();

        DB::table('pagos')->insert([
            ['codigo' => 'PAY-OWNER', 'fecha' => '2026-11-09', 'monto' => 450, 'operacion' => 'OWNER-OLD', 'usuario_id' => $owner->id],
            ['codigo' => 'PAY-OWNER', 'fecha' => '2026-11-10', 'monto' => 280, 'operacion' => 'OWNER-NEW', 'usuario_id' => $owner->id],
            ['codigo' => 'PAY-OTHER', 'fecha' => '2026-11-11', 'monto' => 999, 'operacion' => 'OTHER', 'usuario_id' => $other->id],
        ]);

        $this->actingAs($owner)->get('/inicio')
            ->assertViewHas('recentPayments', function ($payments): bool {
                return count($payments) === 2
                    && $payments[0]->codigo === 'PAY-OWNER'
                    && $payments[0]->fecha === '2026-11-10'
                    && $payments[0]->cliente_titular === 'Cliente titular';
            });

        $this->actingAs(User::factory()->administrator()->create())->get('/inicio')
            ->assertViewHas('recentPayments', fn ($payments) => count($payments) === 3 && $payments[0]->codigo === 'PAY-OTHER');
    }
}
