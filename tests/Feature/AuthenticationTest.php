<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Testing-only-password-42!';
    private const FAILURE = 'No se pudo iniciar sesión con las credenciales proporcionadas.';

    public function test_login_is_a_server_rendered_blade_form_without_the_inertia_bundle(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertViewIs('auth.login')
            ->assertSee('Bienvenido de nuevo')
            ->assertSee('name="usuario"', false)
            ->assertSee('name="contrasena"', false)
            ->assertSee('name="_token"', false)
            ->assertDontSee('resources/js/app.js', false);
    }

    public function test_successful_login_normalizes_identifier_and_regenerates_session(): void
    {
        $user = User::factory()->create(['usuario' => 'asesor@example.test']);
        $this->withSession(['before_login' => 'preserved']);
        $before = session()->getId();

        $this->post('/login', ['usuario' => ' ASESOR@EXAMPLE.TEST ', 'contrasena' => self::PASSWORD])
            ->assertRedirect('/inicio');

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($before, session()->getId());
    }

    public function test_wrong_missing_and_inactive_accounts_receive_identical_generic_messages(): void
    {
        User::factory()->create(['usuario' => 'active@example.test']);
        User::factory()->inactive()->create(['usuario' => 'inactive@example.test']);

        foreach ([
            ['active@example.test', 'incorrect-password'],
            ['missing@example.test', self::PASSWORD],
            ['inactive@example.test', self::PASSWORD],
        ] as [$identifier, $password]) {
            $this->post('/login', ['usuario' => $identifier, 'contrasena' => $password])
                ->assertSessionHasErrors(['usuario' => self::FAILURE]);
            $this->assertGuest();
        }
    }

    public function test_five_attempts_lock_the_normalized_identifier_and_ip_for_fifteen_minutes(): void
    {
        $user = User::factory()->create(['usuario' => 'limite@example.test']);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', ['usuario' => $attempt % 2 ? ' LIMITE@EXAMPLE.TEST ' : $user->usuario, 'contrasena' => 'wrong'])
                ->assertSessionHasErrors('usuario');
        }
        $this->post('/login', ['usuario' => $user->usuario, 'contrasena' => self::PASSWORD])
            ->assertStatus(429)->assertHeader('Retry-After');
        $this->assertGuest();

        $this->travel(14)->minutes();
        $this->post('/login', ['usuario' => $user->usuario, 'contrasena' => self::PASSWORD])->assertStatus(429);
        $this->travel(61)->seconds();
        $this->post('/login', ['usuario' => $user->usuario, 'contrasena' => self::PASSWORD])->assertRedirect('/inicio');
        $this->assertAuthenticatedAs($user);
    }

    public function test_ip_limit_stops_attempts_against_many_different_identifiers(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.20']);
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->post('/login', ['usuario' => "absent{$attempt}@example.test", 'contrasena' => 'wrong'])
                ->assertSessionHasErrors('usuario');
        }
        $this->post('/login', ['usuario' => 'another@example.test', 'contrasena' => 'wrong'])
            ->assertStatus(429)->assertHeader('Retry-After');
        $this->assertGuest();
    }

    public function test_successful_login_clears_the_identifier_failure_counter(): void
    {
        $user = User::factory()->create();
        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->post('/login', ['usuario' => $user->usuario, 'contrasena' => 'wrong'])->assertSessionHasErrors('usuario');
        }
        $this->post('/login', ['usuario' => $user->usuario, 'contrasena' => self::PASSWORD])->assertRedirect('/inicio');
        $this->post('/logout')->assertRedirect('/login');
        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->post('/login', ['usuario' => $user->usuario, 'contrasena' => 'wrong'])->assertSessionHasErrors('usuario');
        }
        $this->post('/login', ['usuario' => $user->usuario, 'contrasena' => self::PASSWORD])->assertRedirect('/inicio');
        $this->assertAuthenticatedAs($user);
    }

    public function test_logout_invalidates_session_removes_authentication_and_rotates_csrf_token(): void
    {
        $this->actingAs(User::factory()->create());
        $this->withSession(['private_marker' => 'remove-me', '_token' => 'before-logout-token']);
        $before = session()->getId();

        $this->post('/logout')->assertRedirect('/login')->assertSessionMissing('private_marker');

        $this->assertGuest();
        $this->assertNotSame($before, session()->getId());
        $this->assertNotSame('before-logout-token', session()->token());
        $this->get('/inicio')->assertRedirect('/login');
    }

    public function test_get_cannot_log_a_user_out_and_remember_input_does_not_create_a_recaller_cookie(): void
    {
        $user = User::factory()->create();
        $response = $this->post('/login', ['usuario' => $user->usuario, 'contrasena' => self::PASSWORD, 'remember' => true])
            ->assertRedirect('/inicio');
        foreach ($response->headers->getCookies() as $cookie) {
            $this->assertStringNotContainsString('remember_', $cookie->getName());
        }
        $this->get('/logout')->assertStatus(405);
        $this->assertAuthenticatedAs($user);
    }

    public function test_legacy_password_transition_is_opt_in_and_hashes_only_a_successful_login(): void
    {
        $user = User::factory()->create();
        DB::table('usuarios')->where('id', $user->id)->update(['contrasena' => self::PASSWORD]);
        config(['auth.legacy_passwords' => false]);
        $this->post('/login', ['usuario' => $user->usuario, 'contrasena' => self::PASSWORD])->assertSessionHasErrors('usuario');
        $this->assertGuest();
        $this->assertSame(self::PASSWORD, $user->fresh()->contrasena);

        config(['auth.legacy_passwords' => true]);
        $this->post('/login', ['usuario' => $user->usuario, 'contrasena' => 'wrong'])->assertSessionHasErrors('usuario');
        $this->assertSame(self::PASSWORD, $user->fresh()->contrasena);
        $this->post('/login', ['usuario' => $user->usuario, 'contrasena' => self::PASSWORD])->assertRedirect('/inicio');
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame(self::PASSWORD, $user->fresh()->contrasena);
        $this->assertTrue(Hash::check(self::PASSWORD, $user->fresh()->contrasena));
    }
}
