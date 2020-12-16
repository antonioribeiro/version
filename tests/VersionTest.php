<?php

namespace PragmaRX\Version\Tests;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use PragmaRX\Version\Package\Exceptions\GitTagNotFound;
use PragmaRX\Version\Package\Exceptions\MethodNotFound;
use PragmaRX\Version\Package\Facade as VersionFacade;
use PragmaRX\Version\Package\Support\Constants;
use PragmaRX\Version\Package\Version;
use PragmaRX\Version\Package\Version as VersionService;

class VersionTest extends TestCase
{
    const ABSORB_VERSION = '1.5.12';

    const currentVersion = '1.0.0';

    /**
     * @var VersionService
     */
    protected $version;

    public static $gitVersion;

    public static $remoteVersion;

    protected $currentVersion;

    protected $commit;

    protected $config;

    public function setUp(): void
    {
        parent::setup();

        $this->createGitTag();

        putenv(
            'VERSION_GIT_REMOTE_REPOSITORY=https://github.com/antonioribeiro/version.git'
        );

        $this->version = VersionFacade::instance();

        $this->absorbVersion();

        $this->version->current(); // load config
    }

    protected function createGitTag($version = '0.1.1')
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

        $this->commit = $this->getBuild();

        $this->retrieveRemoteVersion();
    }

    protected function dropAllGitTags()
    {
        chdir(base_path());

        exec('git tag | xargs git tag -d');
    }

    protected function getBuild()
    {
        return substr(exec('git rev-parse --verify HEAD'), 0, 6);
    }

    protected function retrieveRemoteVersion()
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

    protected function removeGitTag()
    {
        chdir(base_path());

        while ($version = exec('git describe 2>/dev/null')) {
            exec("git tag -d $version");
        }
    }

    // ---------------------------------------------------------------------------

    public function testCanInstantiateService()
    {
        $this->assertInstanceOf(VersionService::class, $this->version);
    }

    public function testConfigIsProperlyLoaded()
    {
        $this->assertEquals(
            '{$version-only}[.?={$prerelease}][+?={$buildmetadata}] (commit {$commit})',
            config('version.format.full')
        );
    }

    public function testCanGetVersion()
    {
        $this->assertEquals($this->getFormattedVersion('version %s.%s.%s (commit %s)'), $this->version->current());
    }

    public function getFormattedVersion($format)
    {
        return sprintf(
            $format,
            $this->config['current']['major'],
            $this->config['current']['minor'],
            $this->config['current']['patch'],
            $this->config['current']['commit']
        );
    }

    public function testCanGetCommit()
    {
        $this->assertEquals($this->commit, $this->version->getGit()->getCommit());
        $this->assertEquals($this->commit, $this->version->getGit()->getCommit());
    }

    public function testCanGetVersionParts()
    {
        $this->setConfig('mode', 'increment');

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

    public function testGetCommitByNumber()
    {
        $this->setConfig('mode', Constants::MODE_INCREMENT);

        $this->assertEquals($this->config['current']['commit'], $this->version->commit());
    }

    public function testAddFormat()
    {
        $this->setConfig('format.mine', '{$major}-{$commit}');

        $this->assertEquals("1-{$this->commit}", $this->version->format('mine'));
    }

    public function testFormat()
    {
        $this->assertEquals(
            $this->getFormattedVersion('version %s.%s.%s (commit %s)'),
            $this->version->format('full')
        );

        $this->assertEquals(
            $this->getFormattedVersion('v%s.%s.%s-%s'),
            $this->version->format('compact')
        );
    }

    public function testBladeDirective()
    {
        $result = $this->render(Blade::compileString('MyApp @version'));

        $this->assertEquals(
            $this->getFormattedVersion('MyApp version %s.%s.%s (commit %s)'),
            $result
        );

        $result = $this->render(
            Blade::compileString("Compact: @version('compact')")
        );

        $this->assertEquals($this->getFormattedVersion('Compact: v%s.%s.%s-%s'), $result);
    }

    public function testDirectFromApp()
    {
        $this->assertEquals(
            $this->getFormattedVersion('version %s.%s.%s (commit %s)'),
            app('pragmarx.version')->format('full')
        );
    }

    public function testConfig()
    {
        $this->assertEquals(
            '{$version-only}[.?={$prerelease}][+?={$buildmetadata}] (commit {$commit})',
            config('version.format.full')
        );
    }

    public function testIncrementCommit()
    {
        $commit = $this->config['current']['commit'];

        $this->setConfig('mode', 'increment');

        $this->version->incrementCommit(); // +1

        $this->assertEquals($this->version->incrementHex($commit), $this->version->commit());

        $this->version->incrementCommit(5); // +5

        $this->assertEquals($this->version->incrementHex($commit, 6), $this->version->commit()); // == +6
    }

    public function testIncrementMajor()
    {
        $this->setConfig('mode', 'increment');

        $commit = $this->config['current']['commit'];

        $this->version->incrementMinor();

        // const ABSORB_VERSION = '1.5.12';

        $this->assertEquals(
            'version 1.6.0 (commit '.$commit.')',
            $this->version->format('full')
        );

        $this->version->incrementPatch();

        $this->assertEquals(
            'version 1.6.1 (commit '.$commit.')',
            $this->version->format('full')
        );

        $this->version->incrementMajor();

        $this->assertEquals(
            'version 2.0.0 (commit '.$commit.')',
            $this->version->format('full')
        );

        $this->version->incrementMinor();

        $this->assertEquals(
            'version 2.1.0 (commit '.$commit.')',
            $this->version->format('full')
        );

        $this->version->incrementPatch();

        $this->assertEquals(
            'version 2.1.1 (commit '.$commit.')',
            $this->version->format('full')
        );

        $this->assertEquals($this->config['current']['commit'], $this->version->commit());
    }

    public function testCanRunCommands()
    {
        $this->setConfig('mode', Constants::MODE_INCREMENT);
        $this->setConfig('current.timestamp.mode', Constants::MODE_INCREMENT);
        $this->setConfig('commit.mode', Constants::MODE_INCREMENT);

        Artisan::call('version:show');

        Artisan::call('version:show', ['--suppress-app-name' => true]);

        Artisan::call('version:patch');

        Artisan::call('version:minor');

        Artisan::call('version:major');
        Artisan::call('version:major');
        Artisan::call('version:major');
        Artisan::call('version:major'); // 5

        Artisan::call('version:minor');
        Artisan::call('version:minor');
        Artisan::call('version:minor'); // 3

        Artisan::call('version:patch');
        Artisan::call('version:patch'); // 2

        $this->assertEquals(
            $this->getFormattedVersion('version 5.3.2 (commit '.$this->commit.')'),
            $this->version->format('full')
        );

        Artisan::call('version:commit');

        $this->assertEquals(
            $this->getFormattedVersion('version 5.3.2 (commit '.$this->version->incrementHex($this->commit).')'),
            $this->version->format('full')
        );
    }

    public function testCanGetVersionFromGitLocal()
    {
        $this->createGitTag();

        $this->assertEquals(
            'version '.static::ABSORB_VERSION.' (commit '.$this->config['current']['commit'].')',
            $this->version->format('full')
        );
    }

    public function testRaiseExceptionOnMissingGitTags()
    {
        $this->expectException(GitTagNotFound::class);

        $this->removeGitTag();

        $this->absorbVersion(false);

        $this->assertEquals(
            $this->getFormattedVersion('version %s.%s.%s (commit %s)'),
            $this->version->format('full')
        );
    }

    public function testCanGetVersionFromGitRemote()
    {
        $version = static::$remoteVersion;

        $this->createGitTag($version);

        $this->absorbVersion(false);

        $this->assertEquals(
            "{$version}",
            $this->version->major().
                '.'.
                $this->version->minor().
                '.'.
                $this->version->patch()
        );
    }

    public function testCanUseDefaultConfig()
    {
        $this->assertEquals(config('version.mode'), Constants::MODE_ABSORB);

        $this->setConfig('mode', Constants::MODE_INCREMENT);

        $this->assertEquals(config('version.mode'), Constants::MODE_INCREMENT);

        $this->version->loadConfig();

        $this->setConfig('mode', Constants::MODE_ABSORB);

        $this->assertEquals(config('version.mode'), Constants::MODE_ABSORB);
    }

    public function testCanReloadConfig()
    {
        exec('rm '.base_path('config/version.yml'));

        $this->version->loadConfig();

        $this->assertEquals(
            'version 1.0.0 (commit 100001)',
            $this->version->format()
        );
    }

    public function testCanCallFormatTypesDinamically()
    {
        $this->assertEquals(
            $this->getFormattedVersion('version %s.%s.%s (commit %s)'),
            $this->version->full()
        );

        $this->assertEquals($this->getFormattedVersion('v%s.%s.%s-%s'), $this->version->compact());

        $this->setConfig(
            'format.awesome',
            'awesome version {$major}.{$minor}.{$patch}'
        );

        $this->assertEquals(
            $this->getFormattedVersion('awesome version %s.%s.%s'),
            $this->version->awesome()
        );

        $this->expectException(MethodNotFound::class);

        $this->version->inexistentMethod();
    }

    public function testCanCallBasicTypesDynamically()
    {
        $this->version->incrementMajor();
        $this->version->incrementMajor();
        $this->version->incrementMajor(); // 4.0.0
        $this->version->incrementMinor();
        $this->version->incrementMinor(); // 4.2.0

        $this->assertEquals('4', $this->version->major());
        $this->assertEquals('2', $this->version->minor());
        $this->assertEquals('0', $this->version->patch());

        $this->assertEquals($this->commit, $this->version->commit());
    }

    public function testDontLoadOnMissingConfiguration()
    {
        $configFile = base_path('config/version.yml');

        $this->assertEquals(config('version.mode'), Constants::MODE_ABSORB);

        $this->version->loadConfig($configFile);

        $this->assertEquals(config('version.mode'), Constants::MODE_ABSORB);

        exec('rm '.$configFile);

        $this->version->loadConfig($configFile);

        $this->assertEquals(config('version.mode'), Constants::MODE_ABSORB);
    }

    public function testVersionAbsorbRaisesExceptionWhenNoTagIsAvailable()
    {
        $this->dropAllGitTags();

        $this->expectException(GitTagNotFound::class);

        Artisan::call('version:absorb');
    }

    public function testVersionAbsorbOff()
    {
        $this->setConfig('mode', 'increment');

        $this->createGitTag(static::ABSORB_VERSION);

        $this->assertEquals(
            'v'.static::ABSORB_VERSION."-{$this->commit}",
            $this->version->format('compact')
        );

        /// Absorb off
        Artisan::call('version:absorb');
        $this->version->loadConfig(base_path('config/version.yml'));
        $this->assertEquals($this->getFormattedVersion('v%s.%s.%s-%s'), $this->version->format('compact'));
    }

    public function testVersionAbsorbVersionOn()
    {
        $this->absorbVersion();

        $this->version->loadConfig(base_path('config/version.yml'));

        $this->setConfig('mode', Constants::MODE_INCREMENT);

        $this->assertEquals(
            'v'.static::ABSORB_VERSION."-{$this->commit}",
            $this->version->format('compact')
        );
    }

    public function testVersionAbsorbCommitOn()
    {
        $this->createGitTag(static::ABSORB_VERSION);

        $this->assertEquals(
            'v'.static::ABSORB_VERSION."-{$this->commit}",
            $this->version->format('compact')
        );

        Artisan::call('version:absorb');
        $this->version->loadConfig(base_path('config/version.yml'));

        $this->assertEquals(
            $this->getFormattedVersion('v%s.%s.%s-%s'),
            $this->version->format('compact')
        );
    }

    public function testVersionAbsorbBothOn()
    {
        $this->createGitTag(static::ABSORB_VERSION);

        $this->assertEquals(
            'v'.static::ABSORB_VERSION."-{$this->commit}",
            $this->version->format('compact')
        );

        $this->absorbVersion();

        $this->setConfig('mode', Constants::MODE_INCREMENT);

        $this->assertEquals(1, config('version.current.major'));
        $this->assertEquals(5, config('version.current.minor'));
        $this->assertEquals(12, config('version.current.patch'));

        $this->assertEquals($this->commit, config('version.current.commit'));
    }

    public function testVersionCannotBeIncrementedWhenAbsorbIsOn()
    {
        Artisan::call('version:major');

        $this->version->loadConfig(base_path('config/version.yml'));

        $this->assertEquals(config('version.current.major'), 1);

        $this->setConfig('mode', Constants::MODE_INCREMENT);

        Artisan::call('version:major');

        $this->version->loadConfig(base_path('config/version.yml'));

        $this->assertEquals(config('version.current.major'), 2);
    }

    public function testTimestamp()
    {
        $this->assertEquals(
            config('version.current.timestamp.year'),
            $this->version->format('timestamp-year')
        );

        $this->assertEquals(
            sprintf(
                '%s-%s-%s %s:%s:%s',
                config('version.current.timestamp.year'),
                config('version.current.timestamp.month'),
                config('version.current.timestamp.day'),
                config('version.current.timestamp.hour'),
                config('version.current.timestamp.minute'),
                config('version.current.timestamp.second')
            ),
            $this->version->format('timestamp-datetime')
        );
    }

    public function testMethods()
    {
        $this->assertEquals($this->version->version(), $this->getFormattedVersion('version %s.%s.%s (commit %s)'));
        $this->assertEquals($this->version->commit(), config('version.current.commit'));
        $this->assertEquals($this->version->major(), config('version.current.major'));
        $this->assertEquals($this->version->minor(), config('version.current.minor'));
        $this->assertEquals($this->version->patch(), config('version.current.patch'));
        $this->assertEquals($this->version->full(), $this->getFormattedVersion('version %s.%s.%s (commit %s)'));
        $this->assertEquals($this->version->compact(), $this->getFormattedVersion('v%s.%s.%s-%s'));
        $this->assertEquals($this->version->format('full'), $this->getFormattedVersion('version %s.%s.%s (commit %s)'));
        $this->assertEquals($this->version->format('compact'), $this->getFormattedVersion('v%s.%s.%s-%s'));
    }

    /// ----------------------------------------------------------------------------------

    public function tearDown(): void
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

    public function absorbVersion($createGitTag = true)
    {
        if ($createGitTag) {
            $this->createGitTag(static::ABSORB_VERSION);
        }

        $this->setConfig('mode', 'absorb');

        Artisan::call('version:absorb');

        $this->config = $this->version->loadConfig(base_path('config/version.yml'));
    }

    public function setConfig($variable, $value)
    {
        $array = collect(config('version'))->toArray();

        Arr::set($array, $variable, $value);

        config(['version' => $array]);
    }
}
