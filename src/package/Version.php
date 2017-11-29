<?php

namespace PragmaRX\Version\Package;

use PragmaRX\Version\Package\Support\Cache;
use PragmaRX\Version\Package\Support\Increment;
use PragmaRX\Version\Package\Exceptions\GitTagNotFound;

class Version
{
    use Cache, Increment;

    const VERSION_CACHE_KEY = 'build';

    const BUILD_CACHE_KEY = 'build';

    const BUILD_MODE_NUMBER = 'number';

    const BUILD_MODE_GIT_LOCAL = 'git-local';

    const BUILD_MODE_GIT_REMOTE = 'git-remote';

    const DEFAULT_FORMAT = 'full';

    const VERSION_SOURCE_CONFIG = 'config';

    const VERSION_SOURCE_GIT = 'git';

    /**
     * The config loader.
     *
     * @var \PragmaRX\YamlConf\Package\YamlConf
     */
    protected $config;

    /**
     * The config file.
     *
     * @var string
     */
    private $configFile;

    /**
     * Version constructor.
     */
    public function __construct()
    {
        $this->config = app('pragmarx.yaml-conf');
    }

    /**
     * Get config value.
     *
     * @param $string
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    protected function config($string)
    {
        return config("version.{$string}");
    }

    /**
     * Execute an shell command.
     *
     * @param $command
     * @return string
     */
    private function shell($command)
    {
        chdir(base_path());

        return @exec($command);
    }

    /**
     * Get the current git commit number, to be used as build number.
     *
     * @return string
     */
    private function getGitCommit()
    {
        if ($value = $this->cacheGet($key = $this->key(static::VERSION_CACHE_KEY))) {
            return $value;
        }

        $value = substr($this->shell($this->makeGitHashRetrieverCommand()), 0, $this->config('build.length'));

        $this->cachePut($key, $value);

        return $value;
    }

    /**
     * Get the git hash retriever command.
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    private function getGitHashRetrieverCommand()
    {
        return $this->config('build.mode') === static::BUILD_MODE_GIT_LOCAL
            ? $this->config('git.local')
            : $this->config('git.remote');
    }

    /**
     * Get a version.
     *
     * @param $type
     * @return string
     */
    private function getVersion($type)
    {
        return $this->isVersionComingFromGit()
                ? $this->gitVersion($type)
                : $this->config("current.{$type}");
    }

    /**
     * Get the current app version from Git.
     */
    private function getVersionFromGit()
    {
        if ($value = $this->cacheGet($key = $this->key(static::VERSION_CACHE_KEY))) {
            return $value;
        }

        $value = substr($this->shell($this->config('git.version.command')), 0, 64);

        if (!empty($value)) {
            $this->cachePut($key, $value);
        }

        return $value;
    }

    /**
     * Get version from the git repository.
     *
     * @param $type
     * @throws GitTagNotFound
     * @return string
     */
    private function gitVersion($type)
    {
        preg_match_all($this->config('git.version.matcher'), $this->getVersionFromGit(), $matches);

        if (empty($matches[0])) {
            throw new GitTagNotFound('No git tags not found in this repository');
        }

        return [
            'major' => $matches[1][0],

            'minor' => $matches[2][0],

            'patch' => $matches[3][0],

            'build' => $matches[4][0],
        ][$type];
    }

    /**
     * Check if git is the current version source.
     *
     * @return bool
     */
    private function isVersionComingFromGit()
    {
        return $this->config('version_source') == static::VERSION_SOURCE_GIT;
    }

    /**
     * Make the git hash retriever command.
     *
     * @return mixed
     */
    private function makeGitHashRetrieverCommand()
    {
        return str_replace(
            '{$repository}',
            $this->config('build.repository'),
            $this->getGitHashRetrieverCommand()
        );
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
                $this->config('build.repository'),
                $this->build(),
            ],
            $string
        );
    }

    /**
     * Get the current version.
     *
     * @return string
     */
    public function version()
    {
        return $this->replaceVariables($this->makeVersion());
    }

    /**
     * Get the current build.
     *
     * @return mixed
     */
    public function build()
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
     * @return mixed
     */
    public function format($type = null)
    {
        $type = $type ?: static::DEFAULT_FORMAT;

        return $this->replaceVariables($this->config("format.{$type}"));
    }

    /**
     * Set the current config file.
     *
     * @param $file
     */
    public function setConfigFile($file)
    {
        $this->configFile = $file;
    }
}
