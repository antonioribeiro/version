<?php

namespace PragmaRX\Version\Package\Support;

use Exception;
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

    private function cleanOutput($getOutput)
    {
        return trim(str_replace("\n", '', $getOutput));
    }

    /**
     * Break and extract version from string.
     *
     * @param $string
     *
     * @throws GitTagNotFound
     *
     * @return array
     */
    public function extractVersion($string)
    {
        preg_match_all(
            $this->config->get('git.version.matcher'),
            $string,
            $matches
        );

        if (empty($matches[0])) {
            throw new GitTagNotFound('No git tags found in this repository');
        }

        return $matches;
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
     * @param string|null $mode
     *
     * @return string
     */
    public function makeGitVersionRetrieverCommand($mode = null)
    {
        $mode = is_null($mode) ? $this->config->get('version_source') : $mode;

        return $this->searchAndReplaceRepository(
            $this->config->get('git.version.' . $mode)
        );
    }

    /**
     * Get the current git commit number, to be used as build number.
     *
     * @param string|null $mode
     *
     * @return string
     */
    public function getCommit($mode = null)
    {
        return $this->getFromGit(
            $this->makeGitHashRetrieverCommand($mode),
            Constants::VERSION_CACHE_KEY,
            $this->config->get('build.length', 6)
        );
    }

    /**
     * Get the git hash retriever command.
     *
     * @param string|null $mode
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    public function getGitHashRetrieverCommand($mode = null)
    {
        $mode = is_null($mode) ? $this->config->get('build.mode') : $mode;

        return $this->config->get('git.' . $mode);
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
     *
     * @param null $mode
     *
     * @return bool|mixed|null|string
     */
    public function getVersionFromGit($mode = null)
    {
        return $this->getFromGit(
            $this->makeGitVersionRetrieverCommand($mode),
            Constants::BUILD_CACHE_KEY
        );
    }

    /**
     * @param $matches
     *
     * @return null
     */
    private function getMatchedVersionItem($matches, $index)
    {
        return isset($matches[$index][0]) ? $matches[$index][0] : null;
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
        $version = $this->extractVersion($this->getVersionFromGit());

        return [
            'major' => $this->getMatchedVersionItem($version, 1),

            'minor' => $this->getMatchedVersionItem($version, 2),

            'patch' => $this->getMatchedVersionItem($version, 3),

            'build' => $this->getMatchedVersionItem($version, 4),
        ][$type];
    }

    /**
     * Check if git is the current version source.
     *
     * @return bool
     */
    public function isVersionComingFromGit()
    {
        return $this->config->get('version_source') !==
            Constants::VERSION_SOURCE_CONFIG;
    }

    /**
     * Make the git hash retriever command.
     *
     * @param string|null $mode
     *
     * @return mixed
     */
    public function makeGitHashRetrieverCommand($mode)
    {
        return $this->searchAndReplaceRepository(
            $this->getGitHashRetrieverCommand($mode)
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
     * @throws Exception
     *
     * @return string
     */
    protected function shell($command)
    {
        $process = new Process($command, $this->getBasePath());

        $process->run();

        if (!$process->isSuccessful()) {
            return '';
        }

        return $this->cleanOutput($process->getOutput());
    }

    /**
     * Get the current git root path.
     *
     * @return string
     */
    public function getBasePath()
    {
        return base_path();
    }
}
