<?php

namespace PragmaRX\Version\Package;

use PragmaRX\Version\Package\Support\Cache;

class Version
{
    use Cache;

    /**
     * The cache key suffix for build.
     */
    const BUILD_CACHE_KEY  = 'build';

    const BUILD_MODE_NUMBER = 'number';

    const BUILD_MODE_GIT_LOCAL = 'git-local';

    const BUILD_MODE_GIT_REMOTE = 'git-remote';

    /**
     * The config loader.
     *
     * @var \PragmaRX\YamlConf\Package\YamlConf
     */
    protected $config;

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
     * @return \Illuminate\Config\Repository|mixed
     */
    protected function config($string)
    {
        return config("version.{$string}");
    }

    /**
     * Get the current git commit number, to be used as build number.
     *
     * @return string
     */
    private function getGitCommit()
    {
        if ($value = $this->cacheGet($key = $this->key(static::BUILD_CACHE_KEY))) {
            return $value;
        }

        $value = substr(@exec($this->makeGitHashRetrieverCommand()), 0, $this->config('build.length'));

        $this->cachePut($key, $value);

        return $value;
    }

    private function getGitHashRetrieverCommand()
    {
        return $this->config('build.mode') === static::BUILD_MODE_GIT_LOCAL
            ? $this->config('build.git-local')
            : $this->config('build.git-remote');
    }

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
                $this->config('current.major'),
                $this->config('current.minor'),
                $this->config('current.patch'),
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
     * @return mixed
     */
    public function format($type)
    {
        return $this->replaceVariables($this->config("format.{$type}"));
    }

    /**
     * Get a properly formatted version.
     *
     * @return integer
     */
    public function incrementBuild()
    {
        return 12455;
    }
}
