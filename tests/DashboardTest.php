<?php

namespace PragmaRX\Version\Tests;

use Illuminate\Support\Facades\Cache;
use PragmaRX\Version\Package\Facade as VersionFacade;
use PragmaRX\Version\Package\Version as VersionService;

class VersionTest extends TestCase
{
    /**
     * @var VersionService
     */
    private $version;

    const currentVersion = '1.0.0';

    private function getBuild()
    {
        return substr(exec('git ls-remote https://github.com/antonioribeiro/version.git refs/heads/master'), 0, 6);
    }

    public function setUp()
    {
        parent::setup();

        putenv('APP_GIT_REPOSITORY=https://github.com/antonioribeiro/version.git');

        $this->version = VersionFacade::instance();
    }

    public function test_can_instantiate_service()
    {
        $this->assertInstanceOf(VersionService::class, $this->version);
    }

    public function test_can_get_version()
    {
        $this->assertEquals(static::currentVersion, $this->version->version());
    }

    public function test_can_get_build()
    {
        $value = $this->getBuild();

        $this->assertEquals($value, $this->version->build());
        $this->assertEquals($value, $this->version->build());
    }

    public function test_uncache()
    {
        $value = $this->getBuild();

        config(['version.cache.enabled' => false]);

        $this->assertEquals($value, $this->version->build());
    }

    public function test_get_build_by_value()
    {
        config(['version.build.value' => $value = 'anyval']);

        $this->assertEquals($value, $this->version->build());
    }

    public function test_refresh_build()
    {
        $this->assertEquals($this->getBuild(), $this->version->refreshBuild());
    }

    public function test_format()
    {
        $build = $this->getBuild();

        $this->assertEquals("version 1.0.0 (build {$build})", $this->version->format('full'));
        $this->assertEquals("v. 1.0.0-{$build}", $this->version->format('compact'));
    }

    public function test_add_format()
    {
        $build = $this->getBuild();

        config(['version.format.mine' => $value = '{$major}-{$build}']);

        $this->assertEquals("1-{$build}", $this->version->format('mine'));
    }
}
