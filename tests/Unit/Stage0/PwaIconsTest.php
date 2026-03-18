<?php

namespace Tests\Unit\Stage0;

use PHPUnit\Framework\TestCase;

class PwaIconsTest extends TestCase
{
    /** Иконка 192x192 существует */
    public function test_icon_192_exists(): void
    {
        $iconPath = dirname(__DIR__, 3) . '/public/icons/icon-192x192.png';

        $this->assertFileExists($iconPath);
    }

    /** Иконка 512x512 существует */
    public function test_icon_512_exists(): void
    {
        $iconPath = dirname(__DIR__, 3) . '/public/icons/icon-512x512.png';

        $this->assertFileExists($iconPath);
    }
}
