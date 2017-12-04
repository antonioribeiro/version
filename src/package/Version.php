<?php

namespace PragmaRX\Version\Package;

use PragmaRX\Version\Package\Exceptions\MethodNotFound;
use PragmaRX\Version\Package\Support\Cache;
use PragmaRX\Version\Package\Support\Config;
use PragmaRX\Version\Package\Support\Git;
use PragmaRX\Version\Package\Support\Increment;
use PragmaRX\Yaml\Package\Yaml;

class Version
{
    const VERSION_CACHE_KEY = 'version';

    const BUILD_CACHE_KEY = 'build';

    const BUILD_MODE_NUMBER = 'number';

    const BUILD_MODE_GIT_LOCAL = 'git-local';

    const BUILD_MODE_GIT_REMOTE = 'git-remote';

    const DEFAULT_FORMAT = 'full';

    const VERSION_SOURCE_CONFIG = 'config';

    const VERSION_SOURCE_GIT_LOCAL = 'git-local';

    const VERSION_SOURCE_GIT_REMOTE = 'git-remote';

    protected $yaml;

    protected $cache;

    protected $config;

    protected $git;

    protected $increment;

    /**
     * Version constructor.
     *
     * @param Cache|null     $cache
     * @param Config|null    $config
     * @param Git|null       $git
     * @param Increment|null $increment
     * @param Yaml           $yaml
     */
    public function __construct(Cache $cache = null,
                                Config $config = null,
                                Git $git = null,
                                Increment $increment = null,
                                Yaml $yaml)
    {
        $this->instantiate($cache, $config, $git, $increment, $yaml);
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
        if (!is_null($version = $this->format($name))) {
            return $version;
        }

        throw new MethodNotFound("Method '{$name}' doesn't exists in this object.");
    }

    /**
     * Get a version.
     *
     * @param $type
     *
     * @return string
     */
    private function getVersion($type)
    {
        return $this->isVersionComingFromGit()
                ? $this->gitVersion($type)
                : $this->config("current.{$type}");
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
    private function instantiate($cache, $config, $git, $increment, $yaml)
    {
        $this->instantiateClass($cache, 'cache', Cache::class);

        $this->instantiateClass($config, 'config', Config::class);

        $this->instantiateClass($git, 'git', Git::class);

        $this->instantiateClass($increment, 'increment', Increment::class);

        $this->instantiateClass($yaml, 'yaml', 'pragmarx.yaml');
    }

    /**
     * Instantiate a class.
     *
     * @param $instance  object
     * @param $property  string
     * @param $class     string
     */
    private function instantiateClass($instance, $property, $class)
    {
        $this->{$property} = is_null($instance)
            ? $instance = app($class)
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
            [
                '{$major}',
                '{$minor}',
                '{$patch}',
                '{$repository}',
                '{$build}',
            ],
            [
                $this->getVersion('major'),
                $this->getVersion('minor'),
                $this->getVersion('patch'),
                $this->getGitRepository(),
                $this->getBuild(),
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
        if ($this->isVersionComingFromGit() && $value = $this->gitVersion('build')) {
            return $value;
        }

        if ($value = $this->config('build.mode') === static::BUILD_MODE_NUMBER) {
            return $this->config('build.number');
        }

        return $this->getGitCommit();
    }

    /**
     * Make version string.
     *
     * @return string
     */
    protected function makeVersion()
    {
        return $this->config('current.format');
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
        $type = $type ?: static::DEFAULT_FORMAT;

        if (!is_null($value = $this->config("format.{$type}"))) {
            return $this->replaceVariables($value);
        }
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
     * @return Collection
     */
    public function loadConfig($path = null)
    {
        return $this->config->loadConfig($path);
    }
}
