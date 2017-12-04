<?php

namespace PragmaRX\Version\Package;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use PragmaRX\Version\Package\Console\Commands\Build;
use PragmaRX\Version\Package\Console\Commands\Major;
use PragmaRX\Version\Package\Console\Commands\Minor;
use PragmaRX\Version\Package\Console\Commands\Patch;
use PragmaRX\Version\Package\Console\Commands\Refresh;
use PragmaRX\Version\Package\Console\Commands\Show;
use PragmaRX\Version\Package\Console\Commands\Version as VersionCommand;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    protected $commandList = [
        'pragmarx.version.command' => VersionCommand::class,

        'pragmarx.version.build.command' => Build::class,

        'pragmarx.version.show.command' => Show::class,

        'pragmarx.version.major.command' => Major::class,

        'pragmarx.version.minor.command' => Minor::class,

        'pragmarx.version.patch.command' => Patch::class,

        'pragmarx.version.refresh.command' => Refresh::class,
    ];

    /**
     * Boot Service Provider.
     */
    public function boot()
    {
        $this->publishConfiguration();
    }

    /**
     * Get the config file path.
     *
     * @return string
     */
    private function getConfigFile()
    {
        return config_path('version.yml');
    }

    /**
     * Get the original config file.
     *
     * @return string
     */
    private function getConfigFileStub()
    {
        return __DIR__.'/../config/version.yml';
    }

    /**
     * Configure config path.
     */
    private function publishConfiguration()
    {
        $this->publishes([
             $this->getConfigFileStub() => $this->getConfigFile(),
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
     * Register Blade directives.
     */
    private function registerBlade()
    {
        Blade::directive('version', function ($format = Version::DEFAULT_FORMAT) {
            return "<?php echo app('pragmarx.version')->format($format); ?>";
        });
    }

    /**
     * Register command.
     *
     * @param $name
     * @param $commandClass string
     */
    private function registerCommand($name, $commandClass)
    {
        $this->app->singleton($name, function () use ($commandClass) {
            return new $commandClass();
        });

        $this->commands($name);
    }

    /**
     * Register Artisan commands.
     */
    private function registerCommands()
    {
        collect($this->commandList)->each(function ($commandClass, $key) {
            $this->registerCommand($key, $commandClass);
        });
    }

    /**
     * Register service service.
     */
    private function registerService()
    {
        $this->app->singleton('pragmarx.version', function (Application $app) {
            $version = new Version(

            );

            $version->setConfigFileStub($this->getConfigFileStub());

            $version->loadConfig($this->getConfigFile());

            return $version;
        });
    }
}
