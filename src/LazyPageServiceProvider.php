<?php

namespace Step2dev\LazyPage;

use Illuminate\Support\Facades\Route;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Step2dev\LazyPage\Commands\ImportLegacyPagesCommand;
use Step2dev\LazyPage\Contracts\PageUrlGeneratorContract;
use Step2dev\LazyPage\Http\Controllers\PageController;
use Step2dev\LazyPage\Models\Page as PageModel;
use Step2dev\LazyPage\Models\PageTranslation;
use Step2dev\LazyPage\Observers\PageObserver;
use Step2dev\LazyPage\Observers\PageTranslationObserver;
use Step2dev\LazyPage\Services\LazyPageManager;
use Step2dev\LazyPage\Services\PageCache;
use Step2dev\LazyPage\Services\PageResolver;
use Step2dev\LazyPage\Services\PageUrlGenerator;

class LazyPageServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('lazy-page')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations([
                '2026_09_25_000000_create_lazy_pages_table',
            ])
            ->runsMigrations()
            ->hasCommand(ImportLegacyPagesCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(PageCache::class);
        $this->app->singleton(PageResolver::class);
        $this->app->singleton(LazyPageManager::class);

        $this->app->bind(PageUrlGeneratorContract::class, function ($app): PageUrlGeneratorContract {
            $class = (string) config('lazy-page.url_generator', PageUrlGenerator::class);

            /** @var PageUrlGeneratorContract $generator */
            $generator = $app->make($class);

            return $generator;
        });
    }

    public function packageBooted(): void
    {
        PageModel::observe(PageObserver::class);
        PageTranslation::observe(PageTranslationObserver::class);

        if (! (bool) config('lazy-page.route.enabled', false)) {
            return;
        }

        $prefix = trim((string) config('lazy-page.route.prefix', 'page'), '/');
        $name = (string) config('lazy-page.route.name', 'lazy-page.show');
        $middleware = (array) config('lazy-page.route.middleware', ['web']);

        Route::middleware($middleware)
            ->prefix($prefix)
            ->get('{page}', PageController::class)
            ->where('page', '.*')
            ->name($name);
    }
}
