<?php

namespace Tests\Feature\Stage0;

use Tests\TestCase;

class ApiExceptionTest extends TestCase
{
    /** API 404 возвращает JSON, а не HTML */
    public function test_api_404_returns_json(): void
    {
        $response = $this->getJson('/api/nonexistent-endpoint');

        $response->assertStatus(404);
        $response->assertHeader('Content-Type', 'application/json');
    }

    /** API 404 содержит поле message */
    public function test_api_404_contains_message(): void
    {
        $response = $this->getJson('/api/nonexistent-endpoint');

        $response->assertJsonStructure(['message']);
    }
}
