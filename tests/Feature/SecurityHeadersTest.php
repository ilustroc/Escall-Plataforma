<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    public function test_html_responses_have_security_headers_and_no_server_signature(): void
    {
        $response = $this->get('/login')->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'same-origin')
            ->assertHeader('Permissions-Policy')
            ->assertHeaderMissing('X-Powered-By')
            ->assertHeaderMissing('Strict-Transport-Security');

        $policy = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($policy);
        $this->assertStringContainsString("frame-ancestors 'none'", $policy);
        $this->assertStringContainsString("base-uri 'self'", $policy);
        $this->assertStringContainsString("script-src 'self'", $policy);
        $this->assertStringNotContainsString("'unsafe-eval'", $policy);
    }

    public function test_https_session_cookie_is_secure_httponly_and_samesite_lax(): void
    {
        config(['session.secure' => true]);
        $response = $this->get('https://plataforma.example.test/login')->assertOk()
            ->assertHeader('Strict-Transport-Security');
        $cookie = collect($response->headers->getCookies())->first(fn ($cookie) => $cookie->getName() === config('session.cookie'));

        $this->assertNotNull($cookie, 'Session cookie must be emitted by the real session middleware.');
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', $cookie->getSameSite());
        $this->assertStringContainsString('max-age=31536000', $response->headers->get('Strict-Transport-Security'));
    }

    public function test_headers_are_also_present_on_errors(): void
    {
        $this->get('/not-a-route')->assertNotFound()->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')->assertHeader('Content-Security-Policy');
    }
}
