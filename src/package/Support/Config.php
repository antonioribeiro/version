<?php

namespace PragmaRX\Version\Package\Support;

use Illuminate\Support\Collection;

class Config
{
    /**
     * The config loader.
     *
     * @var \PragmaRX\Yaml\Package\Yaml
     */
    protected $yaml;

    /**
     * The config file stub.
     *
     * @var string
     */
    protected $configFileStub;

    /**
     * The config file.
     *
     * @var string
     */
    protected $configFile;

    /**
     * Get config value.
     *
     * @param $string
     * @param mixed|null $default
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    protected function get($string, $default = null)
    {
        return config("version.{$string}", $default);
    }

    /**
     * Get config root.
     *
     * @return \Illuminate\Config\Repository|mixed
     *
     * @internal param $string
     */
    protected function getRoot()
    {
        return config('version');
    }

    /**
     * Set the config file stub.
     *
     * @return string
     */
    public function getConfigFileStub()
    {
        return $this->configFileStub;
    }

    /**
     * Load YAML file to Laravel config.
     *
     * @param $path
     *
     * @return mixed
     */
    private function loadToLaravelConfig($path)
    {
        return app('pragmarx.yaml')->loadToConfig($path, 'version');
    }

    /**
     * Set the config file stub.
     *
     * @param string $configFileStub
     */
    public function setConfigFileStub($configFileStub)
    {
        $this->configFileStub = $configFileStub;
    }

    /**
     * Load package YAML configuration.
     *
     * @param $path
     *
     * @return Collection
     */
    public function loadConfig($path = null)
    {
        return $this->loadToLaravelConfig(
            $this->setConfigFile($this->getConfigFile($path))
        );
    }

    /**
     * Get the config file path.
     *
     * @param string|null $file
     *
     * @return string
     */
    public function getConfigFile($file = null)
    {
        $file = $file ?: $this->configFile;

        return file_exists($file)
            ? $file
            : $this->getConfigFileStub();
    }

    /**
     * Update the config file.
     */
    protected function updateConfig($config)
    {
        config(['version' => $config]);

        $this->yaml->saveAsYaml($config, $this->configFile);
    }

    /**
     * Set the current config file.
     *
     * @param $file
     *
     * @return mixed
     */
    public function setConfigFile($file)
    {
        return $this->configFile = $file;
    }
}
