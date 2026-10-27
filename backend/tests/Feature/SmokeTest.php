<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Verifica che l'applicazione costruita da bootstrap/app.php risponda come prima.
 */
class SmokeTest extends TestCase
{
    public function test_la_radice_risponde(): void
    {
        $response = $this->request('GET', '/');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('Landing Page Builder API', (string) $response->getBody());
    }

    public function test_le_rotte_protette_richiedono_il_token(): void
    {
        $this->assertSame(401, $this->request('GET', '/api/pages')->getStatusCode());
    }

    public function test_login_e_rotta_protetta_con_token(): void
    {
        $user = $this->createUser(['email' => 'login@example.test']);

        $login = $this->request('POST', '/api/auth/login', ['email' => 'login@example.test', 'password' => 'password-test']);
        $this->assertSame(200, $login->getStatusCode());
        $this->assertNotEmpty($this->json($login)['token'] ?? null);

        $this->assertSame(200, $this->request('GET', '/api/pages', null, [], $user)->getStatusCode());
    }

    public function test_login_con_password_errata(): void
    {
        $this->createUser(['email' => 'login@example.test']);

        $response = $this->request('POST', '/api/auth/login', ['email' => 'login@example.test', 'password' => 'sbagliata']);
        $this->assertSame(401, $response->getStatusCode());
    }
}
