<?php

namespace Tests\Unit\Stage0;

use PHPUnit\Framework\TestCase;

class EnvExampleTest extends TestCase
{
    private string $envExamplePath;

    protected function setUp(): void
    {
        parent::setUp();
        // Путь к .env.example относительно корня проекта
        $this->envExamplePath = dirname(__DIR__, 3) . '/.env.example';
    }

    /** .env.example существует */
    public function test_env_example_exists(): void
    {
        $this->assertFileExists($this->envExamplePath);
    }

    /** .env.example содержит TELEGRAM_BOT_TOKEN */
    public function test_env_example_contains_telegram_bot_token(): void
    {
        $content = file_get_contents($this->envExamplePath);

        $this->assertStringContainsString('TELEGRAM_BOT_TOKEN', $content);
    }

    /** .env.example содержит TELEGRAM_BOT_USERNAME */
    public function test_env_example_contains_telegram_bot_username(): void
    {
        $content = file_get_contents($this->envExamplePath);

        $this->assertStringContainsString('TELEGRAM_BOT_USERNAME', $content);
    }
}
