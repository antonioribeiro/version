<?php

namespace LuanRodrigues\Version\Package;

use LuanRodrigues\Version\Package\Exceptions\MethodNotFound;
use LuanRodrigues\Version\Package\Support\Absorb;
use LuanRodrigues\Version\Package\Support\Cache;
use LuanRodrigues\Version\Package\Support\Config;
use LuanRodrigues\Version\Package\Support\Constants;
use LuanRodrigues\Version\Package\Support\Git;
use LuanRodrigues\Version\Package\Support\Increment;
use LuanRodrigues\Yaml\Package\Yaml;

class Version
{
    /**
     * @var \LuanRodrigues\Yaml\Package\Yaml
     */
    protected $yaml;

    /**
     * @var \LuanRodrigues\Version\Package\Support\Cache
     */
    protected $cache;

    /**
     * @var \LuanRodrigues\Version\Package\Support\Config
     */
    protected $config;

    /**
     * @var \LuanRodrigues\Version\Package\Support\Git
     */
    protected $git;

    /**
     * @var \LuanRodrigues\Version\Package\Support\Increment
     */
    protected $increment;

    /**
     * @var Absorb
     */
    private $absorb;

    /**
     * Version constructor.
     *
     * @param Cache|null     $cache
     * @param Config|null    $config
     * @param Git|null       $git
     * @param Increment|null $increment
     * @param Yaml           $yaml
     * @param Absorb|null    $absorb
     */
    public function __construct(
        Config $config = null,
        Cache $cache = null,
        Git $git = null,
        Increment $increment = null,
        Yaml $yaml = null,
        Absorb $absorb = null
    ) {
        $this->instantiate($cache, $config, $git, $increment, $yaml, $absorb);
    }

    /**
     * Dynamically call format types.
     *
     * @param $name
     * @param array $arguments
     *
     * @throws MethodNotFound
     *
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        if (starts_with($name, 'increment')) {
            return $this->increment->$name(...$arguments);
        }

        if (starts_with($name, 'absorb')) {
            return $this->absorb->$name(...$arguments);
        }

        if (!is_null($version = $this->format($name))) {
            return $version;
        }

        throw new MethodNotFound(
            "Method '{$name}' doesn't exists in this object."
        );
    }

    /**
     * Get a version.
     *
     * @param $type
     *
     * @return string
     */
    protected function getVersion($type)
    {
        return $this->git->isVersionComingFromGit()
            ? $this->git->version($type)
            : $this->config->get("current.{$type}");
    }

    /**
     * Instantiate all dependencies.
     *
     * @param $cache
     * @param $config
     * @param $git
     * @param $increment
     * @param $yaml
     */
    protected function instantiate(
        $cache,
        $config,
        $git,
        $increment,
        $yaml,
        $absorb
    ) {
        $yaml = $this->instantiateClass($yaml ?: app('pragmarx.yaml'), 'yaml');

        $config = $this->instantiateClass($config, 'config', Config::class, [
            $yaml,
        ]);

        $cache = $this->instantiateClass($cache, 'cache', Cache::class, [
            $config,
        ]);

        $git = $this->instantiateClass($git, 'git', Git::class, [
            $config,
            $cache,
        ]);

        $this->instantiateClass($increment, 'increment', Increment::class, [
            $config,
        ]);

        $this->instantiateClass($absorb, 'absorb', Absorb::class, [
            $config,
            $git,
            $cache,
        ]);
    }

    /**
     * Instantiate a class.
     *
     * @param $instance  object
     * @param $property  string
     * @param $class     string
     *
     * @return Yaml|object
     */
    protected function instantiateClass(
        $instance,
        $property,
        $class = null,
        $arguments = []
    ) {
        return $this->{$property} = is_null($instance)
            ? ($instance = new $class(...$arguments))
            : $instance;
    }

    /**
     * Replace text variables with their values.
     *
     * @param $string
     *
     * @return mixed
     */
    protected function replaceVariables($string)
    {
        do {
            $original = $string;

            $string = $this->searchAndReplaceVariables($string);
        } while ($original !== $string);

        return $string;
    }

    /**
     * Search and replace variables ({$var}) in a string.
     *
     * @param $string
     *
     * @return mixed
     */
    protected function searchAndReplaceVariables($string)
    {
        return str_replace(
            ['{$major}', '{$minor}', '{$patch}', '{$repository}', '{$build}', '{$year}', '{$month}', '{$day}', '{$hour}', '{$minute}'],
            [
                $this->getVersion('major'),
                $this->getVersion('minor'),
                $this->getVersion('patch'),
                $this->git->getGitRepository(),
                $this->getBuild(),
                $this->getVersion('year'),
                $this->getVersion('month'),
                $this->getVersion('day'),
                $this->getVersion('hour'),
                $this->getVersion('minute')
            ],
            $string
        );
    }

    /**
     * Get the current version.
     *
     * @return string
     */
    public function current()
    {
        return $this->replaceVariables($this->makeVersion());
    }

    /**
     * Get the current build.
     *
     * @return mixed
     */
    public function getBuild()
    {
        if (
            $this->git->isVersionComingFromGit() &&
            ($value = $this->git->version('build'))
        ) {
            return $value;
        }

        if ($this->config->get('build.mode') === Constants::BUILD_MODE_NUMBER) {
            return $this->config->get('build.number');
        }

        return $this->git->getCommit();
    }

    /**
     * Make version string.
     *
     * @return string
     */
    protected function makeVersion()
    {
        return $this->config->get('current.format');
    }

    /**
     * Get the current object instance.
     *
     * @return $this
     */
    public function instance()
    {
        return $this;
    }

    /**
     * Get a properly formatted version.
     *
     * @param $type
     *
     * @return mixed|null
     */
    public function format($type = null)
    {
        $type = $type ?: Constants::DEFAULT_FORMAT;

        if (!is_null($value = $this->config->get("format.{$type}"))) {
            return $this->replaceVariables($value);
        }
    }

    /**
     * Get a properly formatted version.
     *
     * @param $type
     *
     * @return bool
     */
    public function isInAbsorbMode($type)
    {
        return $this->config->get("{$type}.git_absorb") !== false;
    }

    /**
     * Set the config file stub.
     *
     * @param string $configFileStub
     */
    public function setConfigFileStub($configFileStub)
    {
        $this->config->setConfigFileStub($configFileStub);
    }

    /**
     * Load package YAML configuration.
     *
     * @param $path
     *
     * @return \Illuminate\Support\Collection
     */
    public function loadConfig($path = null)
    {
        return $this->config->loadConfig($path);
    }

    /**
     * Refresh cache.
     */
    public function refresh()
    {
        $this->cache->flush();

        return $this->format('build');
    }
}
