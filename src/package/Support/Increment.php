<?php

namespace PragmaRX\Version\Package\Support;

use Illuminate\Support\Arr;

class Increment
{
    protected $config;

    /**
     * Cache constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
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

        $this->config->update($config);

        return Arr::get($config, $returnKey);
    }

    /**
     * Increment the commit number.
     *
     * @param null $by
     *
     * @return int
     *
     * @internal param null $increment
     */
    public function incrementCommit($by = null)
    {
        $result = $this->increment(function ($config) use ($by) {
            $increment_by = $by ?: $config['commit']['increment-by'];

            $config['current']['commit'] = $this->incrementHex($config['current']['commit'], $increment_by);

            return $config;
        }, 'commit.number');

        event(Constants::EVENT_COMMIT_INCREMENTED);

        return $result;
    }

    /**
     * Increment major version.
     *
     * @return int
     */
    public function incrementMajor()
    {
        $result = $this->increment(function ($config) {
            $config['current']['major']++;

            $config['current']['minor'] = 0;

            $config['current']['patch'] = 0;

            return $config;
        }, 'current.major');

        event(Constants::EVENT_MAJOR_INCREMENTED);

        return $result;
    }

    /**
     * Increment minor version.
     *
     * @return int
     */
    public function incrementMinor()
    {
        $result = $this->increment(function ($config) {
            $config['current']['minor']++;

            $config['current']['patch'] = 0;

            return $config;
        }, 'current.minor');

        event(Constants::EVENT_MINOR_INCREMENTED);

        return $result;
    }

    /**
     * Increment patch.
     *
     * @return int
     */
    public function incrementPatch()
    {
        $result = $this->increment(function ($config) {
            $config['current']['patch']++;

            return $config;
        }, 'current.patch');

        event(Constants::EVENT_PATCH_INCREMENTED);

        return $result;
    }

    public function incrementHex($hex, $by = 1)
    {
        return dechex(hexdec($hex) + $by);
    }
}
