<?php

namespace PragmaRX\Version\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use PragmaRX\Version\Package\ServiceProvider as VersionServiceProvider;
use PragmaRX\YamlConf\Package\ServiceProvider as YamlConfServiceProvider;

abstract class TestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            VersionServiceProvider::class,
            YamlConfServiceProvider::class,
        ];
    }
}
