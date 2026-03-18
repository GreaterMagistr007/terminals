<?php

namespace Tests\Feature\Stage0;

use Tests\TestCase;

class CsrfCookieTest extends TestCase
{
    /** GET /sanctum/csrf-cookie возвращает 204 */
    public function test_csrf_cookie_returns_204(): void
    {
        $response = $this->get('/sanctum/csrf-cookie');

        $response->assertStatus(204);
    }
}
