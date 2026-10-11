<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $cachedConfig = __DIR__.'/../bootstrap/cache/config.php';
        if (is_file($cachedConfig)) {
            @unlink($cachedConfig);
        }

        putenv('APP_ENV=testing');
        putenv('DB_CONNECTION=mysql');
        putenv('DB_HOST=127.0.0.1');
        putenv('DB_PORT=3307');
        putenv('DB_DATABASE=accommerce_test');
        putenv('DB_USERNAME=root');
        putenv('DB_PASSWORD=password');
        $_ENV['APP_ENV'] = 'testing';
        $_ENV['DB_CONNECTION'] = 'mysql';
        $_ENV['DB_HOST'] = '127.0.0.1';
        $_ENV['DB_PORT'] = '3307';
        $_ENV['DB_DATABASE'] = 'accommerce_test';
        $_ENV['DB_USERNAME'] = 'root';
        $_ENV['DB_PASSWORD'] = 'password';

        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
