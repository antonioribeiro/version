<?php

namespace PragmaRX\Version\Package\Support;

use Carbon\Carbon;
use PragmaRX\Version\Package\Version;

class Absorb
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Git
     */
    protected $git;

    /**
     * @var Timestamp
     */
    protected $timestamp;

    /**
     * @var Version
     */
    protected $version;

    /**
     * Absorb constructor.
     *
     * @param Config
     * @param Git
     */
    public function __construct(Config $config, Git $git, Timestamp $timestamp, Version $version)
    {
        $this->config = $config;

        $this->git = $git;

        $this->timestamp = $timestamp;

        $this->version = $version;
    }

    /**
     * Get a properly formatted version.
     *
     * @param bool $force
     *
     * @return bool
     */
    public function absorb()
    {
        $this->absorbVersion();

        $this->absorbCommit();

        $this->absorbTimestamp();

        $this->fireEvent();

        return true;
    }

    /**
     * Absorb the version number from git.
     */
    protected function absorbVersion()
    {
        if (!$this->version->isVersionInAbsorbMode()) {
            return;
        }

        $version = $this->git->extractVersion(
            $this->git->getVersion()
        );

        $config = $this->config->getRoot();

        $config['current']['label'] = $version['label'][0];

        $config['current']['major'] = (int) $version['major'][0];

        $config['current']['minor'] = (int) $version['minor'][0];

        $config['current']['patch'] = (int) $version['patch'][0];

        $config['current']['prerelease'] = $version['prerelease'][0];

        $config['current']['buildmetadata'] = $version['buildmetadata'][0];

        $this->config->update($config);
    }

    /**
     * Absorb the commit from git.
     */
    protected function absorbCommit()
    {
        if (!$this->version->isBuildInAbsorbMode()) {
            return;
        }

        $config = $this->config->getRoot();

        $config['current']['commit'] = $this->git->getCommit() ?? null;

        $this->config->update($config);
    }

    /**
     * Absorb the commit from git.
     */
    protected function absorbTimestamp()
    {
        if (!$this->version->isTimestampInAbsorbMode()) {
            return;
        }

        $config = $this->config->getRoot();

        $date = Carbon::parse($this->git->getTimestamp()) ?? Carbon::now();

        $config['current']['timestamp'] = $this->timestamp->explode($date);

        $this->config->update($config);
    }

    /**
     * Fire absorbed event.
     */
    public function fireEvent()
    {
        event(Constants::EVENT_VERSION_ABSORBED);
    }
}
