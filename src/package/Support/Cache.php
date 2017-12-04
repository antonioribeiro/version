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
    public function __construct(Config $config = null)
    {
        $this->config = is_null($config)
            ? app(Config::class)
            : $config;
    }

    /**
     * Add something to the cache.
     *
     * @param $key
     * @param $value
     * @param int $minutes
     */
    protected function cachePut($key, $value, $minutes = 10)
    {
        IlluminateCache::put($key, $value, $this->config->get('cache.time', $minutes));
    }

    /**
     * Retrieve something from cache.
     *
     * @param $key
     *
     * @return null|mixed
     */
    protected function cacheGet($key)
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
    protected function key($string)
    {
        return $this->config->get('cache.key').'-'.$string;
    }

    /**
     * Get the current object instance.
     */
    public function clearCache()
    {
        IlluminateCache::forget($this->key(static::BUILD_CACHE_KEY));

        IlluminateCache::forget($this->key(static::VERSION_CACHE_KEY));
    }

    /**
     * Get the current object instance.
     */
    public function refresh()
    {
        $this->clearCache();

        return $this->build();
    }
}
