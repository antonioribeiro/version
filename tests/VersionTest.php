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

        $base = realpath(base_path());

        chdir($base);

        exec('rm -rf .git');
        exec('git init');
        exec('git add -A');
        exec('git commit -m "First commit"');
        exec("git tag -a -f v{$version} -m \"version {$version}\"");
        exec("git remote add origin {$base}");

        $this->currentVersion = $version;

        $this->build = $this->getBuild();

        $this->retrieveRemoteVersion();
    }

    private function dropAllGitTags()
    {
        chdir(base_path());

        exec('git tag | xargs git tag -d');
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

        return static::$remoteVersion = substr(
            exec(
                'git ls-remote https://github.com/antonioribeiro/version.git | grep tags/ | grep -v {} | cut -d \/ -f 3 | cut -d v -f 2 | sort --version-sort | tail -1'
            ),
            0,
            6
        );
    }

    private function removeGitTag()
    {
        chdir(base_path());

        while ($version = exec('git describe 2>/dev/null')) {
            exec("git tag -d $version");
        }
    }

    public function setUp(): void
    {
        parent::setup();

        Cache::flush();

        $this->createGitTag();

        putenv(
            'VERSION_GIT_REMOTE_REPOSITORY=https://github.com/antonioribeiro/version.git'
        );

        $this->version = VersionFacade::instance();

        $this->version->current(); // load config

        config(['version.build.mode' => 'git-local']);
    }

    // ---------------------------------------------------------------------------

    public function test_can_instantiate_service()
    {
        $this->assertInstanceOf(VersionService::class, $this->version);
    }

    public function test_config_is_properly_loaded()
    {
        $this->assertEquals(
            'version {$major}.{$minor}.{$patch} (build {$build})',
            config('version.format.full')
        );
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
        $this->assertEquals(
            "version 1.0.0 (build {$this->build})",
            $this->version->format('full')
        );
        $this->assertEquals(
            "v1.0.0-{$this->build}",
            $this->version->format('compact')
        );
    }

    public function test_blade()
    {
        $result = $this->render(Blade::compileString('MyApp @version'));

        $this->assertEquals(
            "MyApp version 1.0.0 (build {$this->build})",
            $result
        );

        $result = $this->render(
            Blade::compileString("Compact: @version('compact')")
        );

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
        $this->assertEquals(
            'version {$major}.{$minor}.{$patch} (build {$build})',
            config('version.format.full')
        );
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

        $this->assertEquals(
            'version 1.1.0 (build 701031)',
            $this->version->format('full')
        );

        $this->version->incrementPatch();

        $this->assertEquals(
            'version 1.1.1 (build 701031)',
            $this->version->format('full')
        );

        $this->version->incrementMajor();

        $this->assertEquals(
            'version 2.0.0 (build 701031)',
            $this->version->format('full')
        );

        $this->version->incrementMinor();

        $this->assertEquals(
            'version 2.1.0 (build 701031)',
            $this->version->format('full')
        );

        $this->version->incrementPatch();

        $this->assertEquals(
            'version 2.1.1 (build 701031)',
            $this->version->format('full')
        );

        $this->assertEquals('701031', $this->version->build());
    }

    public function test_can_run_commands()
    {
        config(['version.build.mode' => 'number']);

        Artisan::call('version:refresh');

        Artisan::call('version:show');

        Artisan::call('version:show', ['--suppress-app-name' => true]);

        Artisan::call('version:build');

        Artisan::call('version:patch');

        Artisan::call('version:minor');

        Artisan::call('version:major');

        $this->assertEquals(
            'version 2.0.0 (build 701032)',
            $this->version->format('full')
        );
    }

    public function test_can_get_version_from_git_local()
    {
        config(['version.version_source' => 'git-local']);

        $this->createGitTag();

        $this->assertEquals(
            'version 0.1.1 (build 3128)',
            $this->version->format('full')
        );

        Cache::flush();

        $this->removeGitTag();

        $this->expectException(GitTagNotFound::class);

        $this->assertEquals(
            'version 0.1.1 (build 3128)',
            $this->version->format('full')
        );
    }

    public function test_can_get_version_from_git_remote()
    {
        config(['version.version_source' => 'git-remote']);

        $version = static::$remoteVersion;

        $this->createGitTag("{$version}.{$this->build}");

        $this->assertEquals(
            "{$version}",
            $this->version->major() .
                '.' .
                $this->version->minor() .
                '.' .
                $this->version->patch()
        );
    }

    public function test_can_cache_version_and_build()
    {
        Cache::flush();

        config(['version.version_source' => 'git-local']);
        config(['version.build.mode' => 'git-local']);

        $this->createGitTag($version = '1.2.35');

        $this->assertEquals(
            "version {$version} (build {$this->build})",
            $this->version->format('full')
        );
        $this->assertEquals(
            "v1.2.35-{$this->build}",
            $this->version->format('compact')
        );
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
        exec('rm ' . base_path('config/version.yml'));

        $this->version->loadConfig();

        $this->assertEquals(
            'version 1.0.0 (build 701031)',
            $this->version->format()
        );
    }

    public function test_can_call_format_types_dinamically()
    {
        $this->assertEquals(
            "version 1.0.0 (build {$this->build})",
            $this->version->full()
        );

        $this->assertEquals("v1.0.0-{$this->build}", $this->version->compact());

        config([
            'version.format.awesome' =>
                'awesome version {$major}.{$minor}.{$patch}',
        ]);

        $this->assertEquals('awesome version 1.0.0', $this->version->awesome());

        $this->expectException(MethodNotFound::class);

        $this->version->inexistentMethod();
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

        exec('rm ' . $configFile);

        $this->version->loadConfig($configFile);

        $this->assertEquals(config('version.build.mode'), 'number');
    }

    public function test_version_date_matcher()
    {
        config(['version.git.build.mode' => 'git-local']);

        config([
            'version.git.version.matcher' => '/(\d{4})(\d{2})(\d{2})(?:\d{2})/',
        ]);

        config([
            'version.format.compact' => 'v.{$major}{$minor}{$patch}-{$build}',
        ]);

        config(['version.version_source' => 'git-local']);

        $this->createGitTag('2017120299');

        $this->assertEquals(
            $version = "v.20171202-{$this->build}",
            $this->version->format('compact')
        );
    }

    public function test_version_absorb_does_nothing_when_not_configured()
    {
        $configFile = base_path('config/version.yml');

        $this->version->loadConfig($configFile);

        $this->assertFalse(config('version.current.git_absorb'));
        $this->assertFalse(config('version.build.git_absorb'));

        Artisan::call('version:absorb');
    }

    public function test_version_absorb_raises_exception_when_no_tag_is_available()
    {
        $this->dropAllGitTags();

        config(['version.current.git_absorb' => 'git-local']);
        config(['version.build.git_absorb' => 'git-local']);

        $this->expectException(GitTagNotFound::class);

        Artisan::call('version:absorb');
    }

    public function test_version_absorb_off()
    {
        config(['version.version_source' => 'git-local']);

        $this->createGitTag('v1.5.12');
        $this->assertEquals(
            "v1.5.12-{$this->build}",
            $this->version->format('compact')
        );

        /// Absorb off
        Artisan::call('version:absorb');
        $this->version->loadConfig(base_path('config/version.yml'));
        $this->assertEquals('v1.0.0-701031', $this->version->format('compact'));
    }

    public function test_version_absorb_version_on()
    {
        config(['version.version_source' => 'git-local']);

        $this->createGitTag('v1.5.12');
        $this->assertEquals(
            "v1.5.12-{$this->build}",
            $this->version->format('compact')
        );

        config(['version.current.git_absorb' => 'git-local']);
        config(['version.build.git_absorb' => 'git-local']);

        Artisan::call('version:absorb');

        $this->version->loadConfig(base_path('config/version.yml'));

        config(['version.version_source' => 'config']);
        config(['version.build.mode' => 'number']);

        $this->assertEquals(
            "v1.5.12-{$this->build}",
            $this->version->format('compact')
        );
    }

    public function test_version_absorb_build_on()
    {
        config(['version.version_source' => 'git-local']);

        $this->createGitTag('v1.5.12');
        $this->assertEquals(
            "v1.5.12-{$this->build}",
            $this->version->format('compact')
        );

        config(['version.current.git_absorb' => false]);
        config(['version.build.git_absorb' => 'git-local']);
        config(['version.version_source' => 'config']);
        Artisan::call('version:absorb');
        $this->version->loadConfig(base_path('config/version.yml'));
        config(['version.version_source' => 'config']);
        $this->assertEquals(
            "v1.0.0-{$this->build}",
            $this->version->format('compact')
        );
    }

    public function test_version_absorb_both_on()
    {
        config(['version.version_source' => 'git-local']);

        $this->createGitTag('v1.5.12');
        $this->assertEquals(
            "v1.5.12-{$this->build}",
            $this->version->format('compact')
        );

        config(['version.build.git_absorb' => 'git-local']);
        config(['version.current.git_absorb' => 'git-local']);

        Artisan::call('version:absorb');

        config(['version.version_source' => 'config']);
        config(['version.build.mode' => 'number']);

        $this->assertEquals(1, config('version.current.major'));
        $this->assertEquals(5, config('version.current.minor'));
        $this->assertEquals(12, config('version.current.patch'));
        $this->assertEquals($this->build, config('version.build.number'));
    }

    public function test_version_cannot_be_incremented_when_absorb_is_on()
    {
        config(['version.build.git_absorb' => 'git-local']);
        config(['version.current.git_absorb' => 'git-local']);

        Artisan::call('version:major');

        $this->version->loadConfig(base_path('config/version.yml'));

        $this->assertEquals(config('version.current.major'), 1);

        config(['version.current.git_absorb' => false]);
        config(['version.build.git_absorb' => false]);

        Artisan::call('version:major');

        $this->version->loadConfig(base_path('config/version.yml'));

        $this->assertEquals(config('version.current.major'), 2);
    }

    public function tearDown(): void
    {
        $this->removeGitTag();
    }

    public function render($view)
    {
        ob_get_level();

        ob_start();

        eval('?' . '>' . $view);

        return ob_get_clean();
    }
}
