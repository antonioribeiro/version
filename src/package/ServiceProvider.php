<?php

namespace PragmaRX\Version\Package;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot Service Provider.
     */
    public function boot()
    {
        $this->publishConfiguration();

        $this->loadConfig();
    }

    private function getConfigFile()
    {
        return config_path('version.yaml');
    }

    /**
     * Load config file to Laravel config
     */
    private function loadConfig()
    {
        $this->app
            ->make('pragmarx.yaml-conf')
            ->loadToConfig($this->getConfigFile(), 'version');
    }

    /**
     * Configure config path.
     */
    private function publishConfiguration()
    {
        $this->publishes([
            __DIR__.'/../config/version.yaml' => $this->getConfigFile(),
        ]);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerService();
    }

    /**
     * Register service service.
     */
    private function registerService()
    {
        $this->app->singleton('pragmarx.version', function ($app) {
            return $app->make(Version::class);
        });
    }
}
