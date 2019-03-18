<?php

namespace PragmaRX\Version\Package\Support;

use Illuminate\Support\Facades\Cache as IlluminateCache;

class Cache
{
    protected $config;

    /**
     * Cache constructor.
     *
     * @param Config|null $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Add something to the cache.
     *
     * @param $key
     * @param $value
     * @param int $minutes
     */
    public function put($key, $value, $minutes = 10)
    {
        IlluminateCache::put(
            $key,
            $value,
            $this->config->get('cache.time', $minutes)
        );
    }

    /**
     * Retrieve something from cache.
     *
     * @param $key
     *
     * @return null|mixed
     */
    public function get($key)
    {
        return $this->config->get('cache.enabled')
            ? IlluminateCache::get($key)
            : null;
    }

    /**
     * Make the cache key.
     *
     * @param $string
     *
     * @return string
     */
    public function key($string)
    {
        return $this->config->get('cache.key').'-'.$string;
    }

    /**
     * Get the current object instance.
     */
    public function flush()
    {
        IlluminateCache::forget($this->key(Constants::BUILD_CACHE_KEY));

        IlluminateCache::forget($this->key(Constants::VERSION_CACHE_KEY));
    }
}
