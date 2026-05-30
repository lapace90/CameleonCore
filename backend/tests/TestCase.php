<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Traits\AuthenticatesUsers;
use Tests\Traits\CreatesTestData;
use Tests\Traits\AssertsApiResponses;
use Tests\Traits\ConfiguresInstance;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Exceptions\Handler;

abstract class TestCase extends BaseTestCase
{
    use AuthenticatesUsers;
    use CreatesTestData;
    use AssertsApiResponses;
    use ConfiguresInstance;

    public function createApplication(): Application
    {
        $app = require __DIR__ . '/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Config instance complète par défaut (tous les modules/productables actifs)
        $this->withFullInstance();

        $this->artisan('migrate:fresh --seed');

        $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ]);
    }

    protected function withExceptionHandling(): self
    {
        $this->app->instance(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            $this->app->make(Handler::class)
        );
        return $this;
    }

    protected function debugMode(): self
    {
        $this->withoutExceptionHandling();
        return $this;
    }
}