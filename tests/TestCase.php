<?php

namespace PragmaRX\Version\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PragmaRX\Version\Package\ServiceProvider as VersionServiceProvider;
use PragmaRX\YamlConf\Package\ServiceProvider as YamlConfServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        copy(__DIR__.'/../src/config/version.yml', config_path('version.yaml'));

        return [
            VersionServiceProvider::class,
            YamlConfServiceProvider::class,
        ];
    }
}
