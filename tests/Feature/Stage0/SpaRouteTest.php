<?php

namespace Tests\Feature\Stage0;

use Tests\TestCase;

class SpaRouteTest extends TestCase
{
    /** GET / возвращает 200 */
    public function test_root_returns_200(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /** Главная страница содержит <div id="app"> */
    public function test_root_contains_app_div(): void
    {
        $response = $this->get('/');

        $response->assertSee('<div id="app"></div>', false);
    }

    /** Главная страница содержит ссылку на manifest.json */
    public function test_root_contains_manifest_link(): void
    {
        $response = $this->get('/');

        $response->assertSee('manifest.json', false);
    }

    /** Catch-all: GET /any-route тоже возвращает 200 */
    public function test_catch_all_returns_200(): void
    {
        $response = $this->get('/any-route');

        $response->assertStatus(200);
    }

    /** Catch-all: содержит <div id="app"> */
    public function test_catch_all_contains_app_div(): void
    {
        $response = $this->get('/any-route');

        $response->assertSee('<div id="app"></div>', false);
    }

    /** Главная страница содержит CSRF meta-tag */
    public function test_root_contains_csrf_meta_tag(): void
    {
        $response = $this->get('/');

        $response->assertSee('name="csrf-token"', false);
    }
}
