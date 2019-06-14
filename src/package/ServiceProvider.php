<?php

namespace LuanRodrigues\Version\Package;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use LuanRodrigues\Version\Package\Console\Commands\Absorb;
use LuanRodrigues\Version\Package\Console\Commands\Build;
use LuanRodrigues\Version\Package\Console\Commands\Major;
use LuanRodrigues\Version\Package\Console\Commands\Minor;
use LuanRodrigues\Version\Package\Console\Commands\Patch;
use LuanRodrigues\Version\Package\Console\Commands\Refresh;
use LuanRodrigues\Version\Package\Console\Commands\Show;
use LuanRodrigues\Version\Package\Console\Commands\Version as VersionCommand;
use LuanRodrigues\Version\Package\Support\Config;
use LuanRodrigues\Version\Package\Support\Constants;
use LuanRodrigues\Yaml\Package\Yaml;

class ServiceProvider extends IlluminateServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * The package config.
     *
     * @var Config
     */
    protected $config;

    /**
     * Console commands to be instantiated.
     *
     * @var array
     */
    protected $commandList = [
        'pragmarx.version.command' => VersionCommand::class,

        'pragmarx.version.build.command' => Build::class,

        'pragmarx.version.show.command' => Show::class,

        'pragmarx.version.major.command' => Major::class,

        'pragmarx.version.minor.command' => Minor::class,

        'pragmarx.version.patch.command' => Patch::class,

        'pragmarx.version.refresh.command' => Refresh::class,

        'pragmarx.version.absorb.command' => Absorb::class,
    ];

    /**
     * Boot Service Provider.
     */
    public function boot()
    {
        $this->publishConfiguration();

        $this->registerBlade();
    }

    /**
     * Get the config file path.
     *
     * @return string
     */
    protected function getConfigFile()
    {
        return config_path('version.yml');
    }

    /**
     * Get the original config file.
     *
     * @return string
     */
    protected function getConfigFileStub()
    {
        return __DIR__.'/../config/version.yml';
    }

    /**
     * Load config.
     */
    protected function loadConfig()
    {
        $this->config = new Config(new Yaml());

        $this->config->setConfigFile($this->getConfigFile());

        $this->config->loadConfig();
    }

    /**
     * Configure config path.
     */
    protected function publishConfiguration()
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

        $this->loadConfig();

        $this->registerCommands();
    }

    /**
     * Register Blade directives.
     */
    protected function registerBlade()
    {
        Blade::directive(
            $this->config->get('blade_directive', 'version'),
            function ($format = Constants::DEFAULT_FORMAT) {
                return "<?php echo app('pragmarx.version')->format($format); ?>";
            }
        );
    }

    /**
     * Register command.
     *
     * @param $name
     * @param $commandClass string
     */
    protected function registerCommand($name, $commandClass)
    {
        $this->app->singleton($name, function () use ($commandClass) {
            return new $commandClass();
        });

        $this->commands($name);
    }

    /**
     * Register Artisan commands.
     */
    protected function registerCommands()
    {
        collect($this->commandList)->each(function ($commandClass, $key) {
            $this->registerCommand($key, $commandClass);
        });
    }

    /**
     * Register service service.
     */
    protected function registerService()
    {
        $this->app->singleton('pragmarx.version', function () {
            $version = new Version($this->config);

            $version->setConfigFileStub($this->getConfigFileStub());

            return $version;
        });
    }
}
