<?php

namespace Jsadways\OdooApi\Providers;

use Illuminate\Support\ServiceProvider;
use Jsadways\OdooApi\Contracts\OdooServiceContract;
use Jsadways\OdooApi\Services\OdooService\OdooService;

class OdooApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $configPath = __DIR__ . '/../config/odoo_api.php';
        $this->mergeConfigFrom($configPath, 'odoo_api');

        $this->app->singleton(OdooServiceContract::class, OdooService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $source = realpath($raw = __DIR__.'/../config/odoo_api.php') ?: $raw;
        $this->publishes([
            $source => config_path('odoo_api.php'),
        ]);

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }
}
