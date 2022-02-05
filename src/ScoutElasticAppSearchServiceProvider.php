<?php

namespace Chrysanthos\ScoutElasticAppSearch;

use Elastic\EnterpriseSearch\Client;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;

class ScoutElasticAppSearchServiceProvider extends ServiceProvider
{
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/config.php' => config_path('app-search.php'),
            ], 'config');
        }

        resolve(EngineManager::class)->extend('elastic-app-search', function ($app) {
            return $app->make(ScoutElasticAppSearchEngine::class);
        });
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'app-search');

        $this->app->singleton(\Elastic\EnterpriseSearch\Client::class, function ($app) {
            $config = $app['config']->get('app-search');

            return new Client([
                'host' => $config['endpoint'],
                'app-search' => [
                    'token' => $config['private_token'],
                ],
            ]);
        });
    }
}
