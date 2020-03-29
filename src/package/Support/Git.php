<?php

namespace PragmaRX\Version\Package\Support;

use Exception;
use PragmaRX\Version\Package\Exceptions\GitTagNotFound;
use Symfony\Component\Process\Process;

class Git
{
    protected $config;

    /**
     * Git constructor.
     *
     * @param Config|null $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
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
            throw new GitTagNotFound('Unable to find git tags in this repository that matches the git.version.matcher pattern in version.yml');
        }

        return $matches;
    }

    /**
     * @return \Illuminate\Config\Repository|mixed
     */
    private function getCommitLength()
    {
        return $this->config->get('commit.length', 6);
    }

    /**
     * Get the commit git repository url.
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
     * @param string|null $from
     *
     * @return string
     */
    public function makeGitVersionRetrieverCommand($from = null)
    {
        return $this->searchAndReplaceRepository(
            $this->config->get('git.version.'.$this->getFrom($from))
        );
    }

    /**
     * Get the current git commit number, to be used as commit number.
     *
     * @param string|null $from
     *
     * @return string
     */
    public function getCommit($from = null)
    {
        return $this->getFromGit(
            $this->makeGitCommitRetrieverCommand($from),
            $this->getCommitLength()
        );
    }

    /**
     * Get the current git date and time.
     *
     * @param string|null $from
     *
     * @return string
     */
    public function getTimestamp($from = null)
    {
        return $this->getFromGit(
            $this->makeGitTimestampRetrieverCommand($from)
        );
    }

    /**
     * Get the git hash retriever command.
     *
     * @param string|null $from
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    public function getGitCommitRetrieverCommand($from = null)
    {
        return $this->config->get('git.commit.'.$this->getFrom($from));
    }

    public function getFrom($from = null)
    {
        return $from ? $from : $this->config->get('git.from');
    }

    /**
     * Get the git date retriever command.
     *
     * @param string|null $from
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    public function getGitTimestampRetrieverCommand($from = null)
    {
        return $this->config->get('git.date.'.$this->getFrom($from));
    }

    /**
     * Execute a shell command to retrieve git values.
     *
     * @param $command
     * @param $keySuffix
     * @param int $length
     *
     * @return bool|mixed|null|string
     */
    protected function getFromGit($command, $length = 256)
    {
        return substr($this->shell($command), 0, $length);
    }

    /**
     * Get the current app version from Git.
     *
     * @param null $from
     *
     * @return bool|mixed|null|string
     */
    public function getVersion($from = null)
    {
        return $this->getFromGit(
            $this->makeGitVersionRetrieverCommand($from)
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
     * Check if git is the current version source.
     *
     * @return bool
     */
    public function isVersionComingFromGit()
    {
        return $this->config->get('mode') ==
            Constants::MODE_ABSORB;
    }

    /**
     * Make the git hash retriever command.
     *
     * @param string|null $from
     *
     * @return mixed
     */
    public function makeGitCommitRetrieverCommand($from)
    {
        return $this->searchAndReplaceRepository(
            $this->getGitCommitRetrieverCommand($from)
        );
    }

    /**
     * Make the git date retriever command.
     *
     * @param string|null $from
     *
     * @return mixed
     */
    public function makeGitTimestampRetrieverCommand($from)
    {
        return $this->searchAndReplaceRepository(
            $this->getGitTimestampRetrieverCommand($from)
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
        $process = Process::fromShellCommandline($command, $this->getBasePath());

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
