<?php

namespace Step2dev\LazyPage\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Step2dev\LazyPage\LazyPageServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName): string => 'Step2dev\\LazyPage\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        (include __DIR__.'/../database/migrations/2026_09_25_000000_create_lazy_pages_table.php')->up();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LazyPageServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config()->set('app.locale', 'en');
        config()->set('app.fallback_locale', 'en');
        config()->set('translatable.locales', ['en', 'uk', 'pl', 'ru']);
        config()->set('translatable.fallback_locale', 'en');
        config()->set('lazy-page.cache.enabled', false);
    }
}
