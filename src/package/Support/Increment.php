<?php

namespace PragmaRX\Version\Package\Support;

trait Increment
{
    /**
     * The config file.
     *
     * @var string
     */
    private $configFile;

    /**
     * Get config value.
     *
     * @param $string
     *
     * @param int $minutes
     * @return \Illuminate\Config\Repository|mixed
     */
    abstract protected function config($string, $minutes = null);

    /**
     * Get a properly formatted version.
     *
     * @param \Closure $incrementer
     * @param $returnKey
     *
     * @return string
     */
    public function increment(\Closure $incrementer, $returnKey)
    {
        $config = $incrementer(config('version'));

        $this->updateConfig($config);

        return array_get($config, $returnKey);
    }

    /**
     * Increment the build number.
     *
     * @param null $increment
     *
     * @return int
     */
    public function incrementBuild($increment = null)
    {
        return $this->increment(function ($config) use ($increment) {
            $increment = $increment ?: $config['build']['increment_by'];

            $config['build']['number'] = $config['build']['number'] + $increment;

            return $config;
        }, 'build.number');
    }

    /**
     * Increment major version.
     *
     * @return int
     */
    public function incrementMajor()
    {
        return $this->increment(function ($config) {
            $config['current']['major']++;

            $config['current']['minor'] = 0;

            $config['current']['patch'] = 0;

            return $config;
        }, 'current.major');
    }

    /**
     * Increment minor version.
     *
     * @return int
     */
    public function incrementMinor()
    {
        return $this->increment(function ($config) {
            $config['current']['minor']++;

            $config['current']['patch'] = 0;

            return $config;
        }, 'current.minor');
    }

    /**
     * Increment patch.
     *
     * @return int
     */
    public function incrementPatch()
    {
        return $this->increment(function ($config) {
            $config['current']['patch']++;

            return $config;
        }, 'current.patch');
    }
}
