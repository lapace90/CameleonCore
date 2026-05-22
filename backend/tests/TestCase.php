<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Traits\AuthenticatesUsers;
use Tests\Traits\CreatesTestData;
use Tests\Traits\AssertsApiResponses;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Exceptions\Handler;

abstract class TestCase extends BaseTestCase
{
    use AuthenticatesUsers;
    use CreatesTestData;
    use AssertsApiResponses;

    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__ . '/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh --seed');

        // Headers par défaut pour API
        $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ]);
    }

    /**
     * Enable exception handling for specific tests
     */
    protected function withExceptionHandling(): self
    {
        $this->app->instance(
            \Illuminate\Contracts\Debug\ExceptionHandler::class,
            $this->app->make(Handler::class)
        );

        return $this;
    }

    /**
     * Debug mode - voir les vraies exceptions  
     */
    protected function debugMode(): self
    {
        $this->withoutExceptionHandling();
        return $this;
    }
}
