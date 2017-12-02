<?php

namespace PragmaRX\Version\Package\Support;

use PragmaRX\Version\Package\Exceptions\GitTagNotFound;
use Symfony\Component\Process\Process;

trait Git
{
    /**
     * Get config value.
     *
     * @param $string
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    abstract protected function config($string);

    /**
     * Get the build git repository url.
     *
     * @return string
     */
    protected function getGitRepository()
    {
        return $this->config('git.repository');
    }

    /**
     * Make a git version command.
     *
     * @return string
     */
    protected function makeGitVersionRetrieverCommand()
    {
        return $this->searchAndReplaceRepository(
            $this->config('git.version.'.$this->config('version_source'))
        );
    }

    /**
     * Get the current git commit number, to be used as build number.
     *
     * @return string
     */
    protected function getGitCommit()
    {
        return $this->getFromGit(
            $this->makeGitHashRetrieverCommand(),
            static::VERSION_CACHE_KEY,
            $this->config('build.length')
        );
    }

    /**
     * Get the git hash retriever command.
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    protected function getGitHashRetrieverCommand()
    {
        return  $this->config('git.'.$this->config('build.mode'));
    }

    /**
     * Get a cached value or execute a shell command to retrieve it.
     *
     * @param $command
     * @param $keySuffix
     * @param int $length
     *
     * @return bool|mixed|null|string
     */
    private function getFromGit($command, $keySuffix, $length = 256)
    {
        if ($value = $this->cacheGet($key = $this->key($keySuffix))) {
            return $value;
        }

        $value = substr($this->shell($command), 0, $length);

        $this->cachePut($key, $value);

        return $value;
    }

    /**
     * Get the current app version from Git.
     */
    protected function getVersionFromGit()
    {
        return $this->getFromGit(
            $this->makeGitVersionRetrieverCommand(),
            static::BUILD_CACHE_KEY
        );
    }

    /**
     * Get version from the git repository.
     *
     * @param $type
     *
     * @throws GitTagNotFound
     *
     * @return string
     */
    protected function gitVersion($type)
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
    protected function isVersionComingFromGit()
    {
        return $this->config('version_source') !== static::VERSION_SOURCE_CONFIG;
    }

    /**
     * Make the git hash retriever command.
     *
     * @return mixed
     */
    protected function makeGitHashRetrieverCommand()
    {
        return $this->searchAndReplaceRepository(
            $this->getGitHashRetrieverCommand()
        );
    }

    /**
     * Get git repository.
     *
     * @param $string
     *
     * @return mixed
     */
    public function searchAndReplaceRepository($string)
    {
        return str_replace('{$repository}', $this->getGitRepository(), $string);
    }

    /**
     * Execute an shell command.
     *
     * @param $command
     *
     * @return string
     */
    private function shell($command)
    {
        $process = new Process($command, base_path());

        $process->run();

        if (!$process->isSuccessful()) {
            return '';
        }

        return $process->getOutput();
    }
}
