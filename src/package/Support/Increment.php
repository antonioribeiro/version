<?php

namespace PragmaRX\Version\Package\Support;

class Increment
{
    protected $config;

    /**
     * Cache constructor.
     *
     * @param Config|null $config
     */
    public function __construct(Config $config = null)
    {
        $this->config = is_null($config)
            ? app(Config::class)
            : $config;
    }

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
        $config = $incrementer($this->config->getRoot());

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
