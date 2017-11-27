<?php

namespace PragmaRX\Version\Package;

class Service
{
    /**
     * @var \PragmaRX\YamlConf\Package\Service
     */
    private $config;

    public function __construct()
    {
        $this->config = app('pragmarx.yaml-conf');
    }

    /**
     * Get the current version.
     *
     * @return string
     */
    public function version()
    {
        return $this->makeVersion();
    }

    /**
     * Get the current build.
     *
     * @return mixed
     */
    public function build()
    {
        return $this->config->get('version.current');
    }

    /**
     * Make version string.
     *
     * @return string
     */
    private function makeVersion()
    {
        $format = $this->config->get('version.format');

        return '';
    }

    public function instance()
    {
        return $this;
    }
}
