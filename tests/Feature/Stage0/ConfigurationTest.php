<?php

namespace Tests\Feature\Stage0;

use Tests\TestCase;

class ConfigurationTest extends TestCase
{
    /** APP_NAME = Terminals */
    public function test_app_name_is_terminals(): void
    {
        $this->assertEquals('Terminals', config('app.name'));
    }

    /** Локаль приложения = ru */
    public function test_locale_is_ru(): void
    {
        $this->assertEquals('ru', config('app.locale'));
    }

    /** Время жизни сессии = 525960 минут */
    public function test_session_lifetime_is_525960(): void
    {
        $this->assertEquals(525960, config('session.lifetime'));
    }
}
