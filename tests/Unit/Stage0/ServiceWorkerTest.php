<?php

namespace Tests\Unit\Stage0;

use PHPUnit\Framework\TestCase;

class ServiceWorkerTest extends TestCase
{
    /** public/sw.js существует */
    public function test_service_worker_exists(): void
    {
        $swPath = dirname(__DIR__, 3) . '/public/sw.js';

        $this->assertFileExists($swPath);
    }
}
