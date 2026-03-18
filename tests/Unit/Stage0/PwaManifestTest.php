<?php

namespace Tests\Unit\Stage0;

use PHPUnit\Framework\TestCase;

class PwaManifestTest extends TestCase
{
    private string $manifestPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manifestPath = dirname(__DIR__, 3) . '/public/manifest.json';
    }

    /** public/manifest.json существует */
    public function test_manifest_exists(): void
    {
        $this->assertFileExists($this->manifestPath);
    }

    /** manifest.json содержит корректный JSON */
    public function test_manifest_is_valid_json(): void
    {
        $content = file_get_contents($this->manifestPath);
        $decoded = json_decode($content, true);

        $this->assertNotNull($decoded, 'manifest.json содержит невалидный JSON');
    }

    /** manifest.json содержит name = Terminals */
    public function test_manifest_name_is_terminals(): void
    {
        $content = file_get_contents($this->manifestPath);
        $decoded = json_decode($content, true);

        $this->assertArrayHasKey('name', $decoded);
        $this->assertEquals('Terminals', $decoded['name']);
    }
}
