<?php

namespace PragmaRX\Version\Tests;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use PragmaRX\Version\Package\Exceptions\GitTagNotFound;
use PragmaRX\Version\Package\Facade as VersionFacade;
use PragmaRX\Version\Package\Version as VersionService;

class VersionTest extends TestCase
{
    /**
     * @var VersionService
     */
    private $version;

    const currentVersion = '1.0.0';

    public static $build;

    public static $gitVersion;

    private function getBuild()
    {
        if (!static::$build) {
            chdir(base_path());

            static::$build = substr(exec('git rev-parse --verify HEAD'), 0, 6);
        }

        return static::$build;
    }

    private function getVersion()
    {
       return '5.5.23';
    }

    public function setUp()
    {
        parent::setup();

        Cache::flush();

        putenv('VERSION_GIT_REMOTE_REPOSITORY=https://github.com/antonioribeiro/version.git');

        config(['version.build.mode' => 'git-local']);

        $this->version = VersionFacade::instance();
    }

    public function test_can_instantiate_service()
    {
        $this->assertInstanceOf(VersionService::class, $this->version);
    }

    public function test_config_is_properly_loaded()
    {
        $this->assertEquals('version {$major}.{$minor}.{$patch} (build {$build})', config('version.format.full'));
    }

    public function test_can_get_version()
    {
        $this->assertEquals(static::currentVersion, $this->version->version());
    }

    public function test_can_get_build()
    {
        $number = $this->getBuild();

        Cache::clear();

        $this->assertEquals($number, $this->version->build());
        $this->assertEquals($number, $this->version->build());
    }

    public function test_uncache()
    {
        $number = $this->getBuild();

        config(['version.cache.enabled' => false]);

        $this->assertEquals($number, $this->version->build());
    }

    public function test_get_build_by_number()
    {
        config(['version.build.mode' => 'number']);

        $this->assertEquals('701031', $this->version->build());
    }

    public function test_refresh_build()
    {
        $this->assertEquals($this->getBuild(), $this->version->refreshBuild());
    }

    public function test_add_format()
    {
        $build = $this->getBuild();

        config(['version.format.mine' => $number = '{$major}-{$build}']);

        $this->assertEquals("1-{$build}", $this->version->format('mine'));
    }

    public function test_format()
    {
        $build = $this->getBuild();

        $this->assertEquals("version 1.0.0 (build {$build})", $this->version->format('full'));
        $this->assertEquals("v1.0.0-{$build}", $this->version->format('compact'));
    }

    public function test_blade()
    {
        $build = $this->getBuild();

        $result = $this->render(Blade::compileString('This is my @version'));

        $this->assertEquals("This is my version 1.0.0 (build {$build})", $result);

        $result = $this->render(Blade::compileString("Compact: @version('compact')"));

        $this->assertEquals("Compact: v1.0.0-{$build}", $result);
    }

    public function test_direct_from_app()
    {
        $build = $this->getBuild();

        $result = app('pragmarx.version')->format('full');

        $this->assertEquals("version 1.0.0 (build {$build})", $result);
    }

    public function test_config()
    {
        $this->assertEquals('version {$major}.{$minor}.{$patch} (build {$build})', config('version.format.full'));
    }

    public function test_increment_build()
    {
        $this->version->incrementBuild();

        config(['version.build.mode' => 'number']);

        $this->assertEquals('701032', $this->version->build());

        $this->version->incrementBuild(5);

        config(['version.build.mode' => 'number']);

        $this->assertEquals('701037', $this->version->build());
    }

    public function test_increment_major()
    {
        config(['version.build.mode' => 'number']);

        $this->version->incrementMinor();

        $this->assertEquals('version 1.1.0 (build 701031)', $this->version->format('full'));

        $this->version->incrementPatch();

        $this->assertEquals('version 1.1.1 (build 701031)', $this->version->format('full'));

        $this->version->incrementMajor();

        $this->assertEquals('version 2.0.0 (build 701031)', $this->version->format('full'));

        $this->version->incrementMinor();

        $this->assertEquals('version 2.1.0 (build 701031)', $this->version->format('full'));

        $this->version->incrementPatch();

        $this->assertEquals('version 2.1.1 (build 701031)', $this->version->format('full'));

        $this->assertEquals('701031', $this->version->build());
    }

    public function test_can_run_commands()
    {
        config(['version.build.mode' => 'number']);

        Artisan::call('version:show');

        Artisan::call('version:build');

        Artisan::call('version:patch');

        Artisan::call('version:minor');

        Artisan::call('version:major');

        $this->assertEquals('version 2.0.0 (build 701032)', $this->version->format('full'));
    }

    public function test_can_get_version_from_git()
    {
        config(['version.version_source' => 'git']);

        chdir(base_path());

        exec('git init');
        exec('git add -A');
        exec('git commit -m "First commit"');
        exec('git tag -a -f v0.1.1.3128 -m "version 0.1.1.3128"');

        $this->assertEquals('version 0.1.1 (build 3128)', $this->version->format('full'));

        exec('git tag -d v0.1.1.3128');

        Cache::flush();

        $this->expectException(GitTagNotFound::class);

        $this->assertEquals('version 0.1.1 (build 3128)', $this->version->format('full'));
    }

    public function render($view)
    {
        ob_get_level();

        ob_start();

        eval('?'.'>'.$view);

        return ob_get_clean();
    }
}
