<?php

namespace PragmaRX\Version\Package;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use PragmaRX\Version\Package\Console\Commands\Build;
use PragmaRX\Version\Package\Console\Commands\Show;

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
        return config_path('version.yml');
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
            __DIR__.'/../config/version.yml' => $this->getConfigFile(),
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

        $this->registerBlade();

        $this->registerCommands();
    }

    /**
     * Register Blade directives
     */
    private function registerBlade()
    {
        Blade::directive('version', function ($format) {
            return "<?php echo app('pragmarx.version')->format($format); ?>";
        });
    }

    /**
     * Register command.
     *
     * @param $name
     * @param \Closure $command
     */
    private function registerCommand($name, \Closure $command)
    {
        $this->app->singleton($name, $command);

        $this->commands($name);
    }

    /**
     * Register Artisan commands
     */
    private function registerCommands()
    {
        $this->registerCommand('pragmarx.version.build.command', function () {
            return new Build();
        });

        $this->registerCommand('pragmarx.version.show.command', function () {
            return new Show();
        });
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
