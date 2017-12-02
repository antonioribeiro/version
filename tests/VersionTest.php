<?php

namespace PragmaRX\Version\Tests;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use PragmaRX\Version\Package\Exceptions\GitTagNotFound;
use PragmaRX\Version\Package\Exceptions\MethodNotFound;
use PragmaRX\Version\Package\Facade as VersionFacade;
use PragmaRX\Version\Package\Version as VersionService;

class VersionTest extends TestCase
{
    /**
     * @var VersionService
     */
    private $version;

    const currentVersion = '1.0.0';

    public static $gitVersion;

    public static $remoteVersion;

    private $currentVersion;

    private $build;

    private function createGitTag($version = '0.1.1.3128')
    {
        if ($this->currentVersion === $version) {
            return;
        }

        chdir(base_path());

        exec('rm -rf .git');
        exec('git init');
        exec('git add -A');
        exec('git commit -m "First commit"');
        exec("git tag -a -f v{$version} -m \"version {$version}\"");

        $this->currentVersion = $version;

        $this->build = $this->getBuild();

        $this->retrieveRemoteVersion();
    }

    private function getBuild()
    {
        return substr(exec('git rev-parse --verify HEAD'), 0, 6);
    }

    private function retrieveRemoteVersion()
    {
        if (isset(static::$remoteVersion)) {
            return static::$remoteVersion;
        }

        var_dump('1 -------------');
        var_dump(exec('git ls-remote https://github.com/antonioribeiro/version.git | grep tags/'));

        var_dump('2 -------------');
        var_dump(exec('git ls-remote https://github.com/antonioribeiro/version.git | grep tags/ | grep -v {}'));

        var_dump('3 -------------');
        var_dump(exec('git ls-remote https://github.com/antonioribeiro/version.git | grep tags/ | grep -v {} | cut -d \/ -f 3'));

        var_dump('4 -------------');
        var_dump(exec('git ls-remote https://github.com/antonioribeiro/version.git | grep tags/ | grep -v {} | cut -d \/ -f 3 | cut -d v -f 2'));

        var_dump('5 -------------');
        var_dump(exec('git ls-remote https://github.com/antonioribeiro/version.git | grep tags/ | grep -v {} | cut -d \/ -f 3 | cut -d v -f 2 | sort --version-sort'));

        var_dump('6 -------------');
        var_dump(exec('git ls-remote https://github.com/antonioribeiro/version.git | grep tags/ | grep -v {} | cut -d \/ -f 3 | cut -d v -f 2 | sort --version-sort | tail -1'));

        return static::$remoteVersion = substr(exec('git ls-remote https://github.com/antonioribeiro/version.git | grep tags/ | grep -v {} | cut -d \/ -f 3 | cut -d v -f 2 | sort --version-sort | tail -1'), 0, 6);
    }

    private function removeGitTag()
    {
        chdir(base_path());

        if (exec('git tag') && $this->currentVersion) {
            exec("git tag -d v{$this->currentVersion}");
        }
    }

    public function setUp()
    {
        parent::setup();

        Cache::flush();

        $this->createGitTag();

        putenv('VERSION_GIT_REMOTE_REPOSITORY=https://github.com/antonioribeiro/version.git');

        $this->version = VersionFacade::instance();

        $this->version->current(); // load config

        config(['version.build.mode' => 'git-local']);
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
        $this->assertEquals(static::currentVersion, $this->version->current());
    }

    public function test_can_get_build()
    {
        Cache::clear();

        $this->assertEquals($this->build, $this->version->build());
        $this->assertEquals($this->build, $this->version->build());
    }

    public function test_can_get_version_parts()
    {
        $this->version->incrementMajor();
        $this->version->incrementMajor();
        $this->version->incrementMinor();
        $this->version->incrementMinor();
        $this->version->incrementMinor();
        $this->version->incrementPatch();
        $this->version->incrementPatch();
        $this->version->incrementPatch();

        $this->assertEquals('3', $this->version->major());
        $this->assertEquals('3', $this->version->minor());
        $this->assertEquals('3', $this->version->patch());

        $this->version->incrementMajor();

        $this->assertEquals('4', $this->version->major());
        $this->assertEquals('0', $this->version->minor());
        $this->assertEquals('0', $this->version->patch());
    }

    public function test_uncache()
    {
        config(['version.cache.enabled' => false]);

        $this->assertEquals($this->build, $this->version->build());
    }

    public function test_get_build_by_number()
    {
        config(['version.build.mode' => 'number']);

        $this->assertEquals('701031', $this->version->build());
    }

    public function test_refresh_build()
    {
        $this->assertEquals($this->build, $this->version->refresh());
    }

    public function test_add_format()
    {
        config(['version.format.mine' => '{$major}-{$build}']);

        $this->assertEquals("1-{$this->build}", $this->version->format('mine'));
    }

    public function test_format()
    {
        $this->assertEquals("version 1.0.0 (build {$this->build})", $this->version->format('full'));
        $this->assertEquals("v1.0.0-{$this->build}", $this->version->format('compact'));
    }

    public function test_blade()
    {
        $result = $this->render(Blade::compileString('MyApp @version'));

        $this->assertEquals("MyApp version 1.0.0 (build {$this->build})", $result);

        $result = $this->render(Blade::compileString("Compact: @version('compact')"));

        $this->assertEquals("Compact: v1.0.0-{$this->build}", $result);
    }

    public function test_direct_from_app()
    {
        $this->assertEquals(
            "version 1.0.0 (build {$this->build})",
            app('pragmarx.version')->format('full')
        );
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

        Artisan::call('version:refresh');

        Artisan::call('version:show');

        Artisan::call('version:build');

        Artisan::call('version:patch');

        Artisan::call('version:minor');

        Artisan::call('version:major');

        $this->assertEquals('version 2.0.0 (build 701032)', $this->version->format('full'));
    }

    public function test_can_get_version_from_git_local()
    {
        config(['version.version_source' => 'git-local']);

        $this->createGitTag();

        $this->assertEquals('version 0.1.1 (build 3128)', $this->version->format('full'));

        Cache::flush();

        $this->removeGitTag();

        $this->expectException(GitTagNotFound::class);

        $this->assertEquals('version 0.1.1 (build 3128)', $this->version->format('full'));
    }

    public function test_can_get_version_from_git_remote()
    {
        config(['version.version_source' => 'git-remote']);

        $this->createGitTag();

        $version = static::$remoteVersion;

        $this->assertEquals("version {$version} (build {$this->build})", $this->version->format('full'));
    }

    public function test_can_cache_version_and_build()
    {
        Cache::flush();

        config(['version.version_source' => 'git-local']);
        config(['version.build.mode' => 'git-local']);

        $this->createGitTag($version = '1.2.35');

        $this->assertEquals("version {$version} (build {$this->build})", $this->version->format('full'));
        $this->assertEquals("v1.2.35-{$this->build}", $this->version->format('compact'));
    }

    public function test_can_use_default_config()
    {
        $this->assertEquals(config('version.build.mode'), 'git-local');

        config(['version.build.mode' => 'git-remote']);

        $this->assertEquals(config('version.build.mode'), 'git-remote');

        $this->version->loadConfig();

        $this->assertEquals(config('version.build.mode'), 'number');
    }

    public function test_can_reload_config()
    {
        exec('rm '.base_path('config/version.yml'));

        $this->version->loadConfig();

        $this->assertEquals('version 1.0.0 (build 701031)', $this->version->format());
    }

    public function test_can_call_format_types_dinamically()
    {
        $this->assertEquals("version 1.0.0 (build {$this->build})", $this->version->full());

        $this->assertEquals("v1.0.0-{$this->build}", $this->version->compact());

        config(['version.format.awesome' => 'awesome version {$major}.{$minor}.{$patch}']);

        $this->assertEquals('awesome version 1.0.0', $this->version->awesome());

        $this->expectException(MethodNotFound::class);

        $this->assertEquals("v1.0.0-{$this->build}", $this->version->inexistentMethod());
    }

    public function test_can_call_basic_types_dynamically()
    {
        $this->version->incrementMajor();
        $this->version->incrementMajor();
        $this->version->incrementMajor(); // 4.0.0
        $this->version->incrementMinor();
        $this->version->incrementMinor(); // 4.2.0

        $this->assertEquals('4', $this->version->major());
        $this->assertEquals('2', $this->version->minor());
        $this->assertEquals('0', $this->version->patch());

        $this->assertEquals($this->build, $this->version->build());
    }

    public function test_dont_load_on_missing_configuration()
    {
        $configFile = base_path('config/version.yml');

        $this->assertEquals(config('version.build.mode'), 'git-local');

        $this->version->loadConfig($configFile);

        $this->assertEquals(config('version.build.mode'), 'number');

        exec('rm '.$configFile);

        $this->version->loadConfig($configFile);

        $this->assertEquals(config('version.build.mode'), 'number');
    }

    public function tearDown()
    {
        $this->removeGitTag();
    }

    public function render($view)
    {
        ob_get_level();

        ob_start();

        eval('?'.'>'.$view);

        return ob_get_clean();
    }
}
