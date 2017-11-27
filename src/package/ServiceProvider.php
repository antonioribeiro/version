<?php

namespace PragmaRX\Version\Package;

use PragmaRX\Version\Package\Service as Version;
use PragmaRX\YamlConf\Package\Service as YamlConf;
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
    }

    /**
     * Configure config path.
     */
    private function publishConfiguration()
    {
        $this->publishes([
            __DIR__.'/../config/version.php' => config_path('version.php'),
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

        $this->registerConfig();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['pragmarx.version'];
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

    /**
     * Register service service.
     */
    private function registerConfig()
    {
        $this->app->singleton('pragmarx.yaml-conf', function ($app) {
            return $app->make(YamlConf::class);
        });
    }
}
