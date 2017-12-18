<?php

namespace PragmaRX\Version\Package\Support;

class Absorb
{
    protected $config;

    /**
     * @var Git
     */
    protected $git;

    /**
     * Cache constructor.
     *
     * @param Config
     * @param Git
     */
    public function __construct(Config $config, Git $git)
    {
        $this->config = $config;

        $this->git = $git;
    }

    /**
     * Get a properly formatted version.
     *
     * @return bool
     */
    public function absorb()
    {
        $this->absorbVersion();

        $this->absorbBuild();

        return true;
    }

    /**
     * Absorb the version number from git.
     */
    private function absorbVersion()
    {
        if (($type = $this->config->get('current.git_absorb')) === false) {
            return;
        }

        $version = $this->git->extractVersion(
            $this->git->getVersionFromGit($type)
        );

        $config = $this->config->getRoot();

        $config['current']['major'] = (int) $version[1][0];

        $config['current']['minor'] = (int) $version[2][0];

        $config['current']['patch'] = (int) $version[3][0];

        $this->config->update($config);
    }

    /**
     * Absorb the build from git.
     */
    private function absorbBuild()
    {
        if (($type = $this->config->get('build.git_absorb')) === false) {
            return;
        }

        $config = $this->config->getRoot();

        $config['build']['number'] = $this->git->getCommit($type);

        $this->config->update($config);
    }
}
