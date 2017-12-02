<?php

namespace PragmaRX\Version\Package;

use PragmaRX\Version\Package\Exceptions\MethodNotFound;
use PragmaRX\Version\Package\Support\Cache;
use PragmaRX\Version\Package\Support\Config;
use PragmaRX\Version\Package\Support\Git;
use PragmaRX\Version\Package\Support\Increment;

class Version
{
    use Cache, Increment, Config, Git;

    const VERSION_CACHE_KEY = 'version';

    const BUILD_CACHE_KEY = 'build';

    const BUILD_MODE_NUMBER = 'number';

    const BUILD_MODE_GIT_LOCAL = 'git-local';

    const BUILD_MODE_GIT_REMOTE = 'git-remote';

    const DEFAULT_FORMAT = 'full';

    const VERSION_SOURCE_CONFIG = 'config';

    const VERSION_SOURCE_GIT_LOCAL = 'git-local';

    const VERSION_SOURCE_GIT_REMOTE = 'git-remote';

    /**
     * Version constructor.
     */
    public function __construct()
    {
        $this->yaml = app('pragmarx.yaml');
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
}
