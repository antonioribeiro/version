<?php

namespace PragmaRX\Version\Package;

class Version
{
    /**
     * @var \PragmaRX\YamlConf\Package\Service
     */
    protected $config;

    public function __construct()
    {
        $this->config = app('pragmarx.yaml-conf');
    }

    protected function config($string)
    {
        return config("version.{$string}");
    }

    private function getCurrentBuild()
    {
        return 'h34F12a';
    }

    protected function replaceVariables($string)
    {
        return str_replace(
            [
                '{$major}',
                '{$minor}',
                '{$patch}',
                '{$build}',
            ],
            [
                $this->config('current.major'),
                $this->config('current.minor'),
                $this->config('current.patch'),
                $this->getCurrentBuild(),
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
    protected function makeVersion()
    {
        return $this->replaceVariables(config('version.current.format'));
    }

    public function instance()
    {
        return $this;
    }
}
