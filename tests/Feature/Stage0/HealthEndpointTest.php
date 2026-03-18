<?php

namespace Tests\Feature\Stage0;

use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    /** GET /api/health возвращает 200 */
    public function test_health_returns_200(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
    }

    /** Ответ содержит status: "ok" */
    public function test_health_contains_status_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertJson(['status' => 'ok']);
    }

    /** Ответ содержит поле timestamp */
    public function test_health_contains_timestamp(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertJsonStructure(['status', 'timestamp']);
    }
}
