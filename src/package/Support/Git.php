<?php

namespace PragmaRX\Version\Package\Support;

use PragmaRX\Version\Package\Exceptions\GitTagNotFound;
use Symfony\Component\Process\Process;

class Git
{
    protected $config;

    protected $cache;

    /**
     * Cache constructor.
     *
     * @param Config|null $config
     * @param Cache|null  $cache
     */
    public function __construct(Config $config, Cache $cache)
    {
        $this->config = $config;

        $this->cache = $cache;
    }

    /**
     * Get the build git repository url.
     *
     * @return string
     */
    public function getGitRepository()
    {
        return $this->config->get('git.repository');
    }

    /**
     * Make a git version command.
     *
     * @return string
     */
    public function makeGitVersionRetrieverCommand()
    {
        return $this->searchAndReplaceRepository(
            $this->config->get('git.version.'.$this->config->get('version_source'))
        );
    }

    /**
     * Get the current git commit number, to be used as build number.
     *
     * @return string
     */
    public function getCommit()
    {
        return $this->getFromGit(
            $this->makeGitHashRetrieverCommand(),
            Constants::VERSION_CACHE_KEY,
            $this->config->get('build.length')
        );
    }

    /**
     * Get the git hash retriever command.
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    public function getGitHashRetrieverCommand()
    {
        return  $this->config->get('git.'.$this->config->get('build.mode'));
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
    protected function getFromGit($command, $keySuffix, $length = 256)
    {
        if ($value = $this->cache->get($key = $this->cache->key($keySuffix))) {
            return $value;
        }

        $value = substr($this->shell($command), 0, $length);

        $this->cache->put($key, $value);

        return $value;
    }

    /**
     * Get the current app version from Git.
     */
    public function getVersionFromGit()
    {
        return $this->getFromGit(
            $this->makeGitVersionRetrieverCommand(),
            Constants::BUILD_CACHE_KEY
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
    public function version($type)
    {
        preg_match_all($this->config->get('git.version.matcher'), $this->getVersionFromGit(), $matches);

        if (empty($matches[0])) {
            throw new GitTagNotFound('No git tags not found in this repository');
        }

        return [
                   'major' => isset($matches[1][0]) ? $matches[1][0] : null,

                   'minor' => isset($matches[2][0]) ? $matches[2][0] : null,

                   'patch' => isset($matches[3][0]) ? $matches[3][0] : null,

                   'build' => isset($matches[4][0]) ? $matches[4][0] : null,
               ][$type];
    }

    /**
     * Check if git is the current version source.
     *
     * @return bool
     */
    public function isVersionComingFromGit()
    {
        return $this->config->get('version_source') !== Constants::VERSION_SOURCE_CONFIG;
    }

    /**
     * Make the git hash retriever command.
     *
     * @return mixed
     */
    public function makeGitHashRetrieverCommand()
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
    protected function shell($command)
    {
        $process = new Process($command, base_path());

        $process->run();

        if (!$process->isSuccessful()) {
            return '';
        }

        return $process->getOutput();
    }
}
