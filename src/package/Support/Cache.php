<?php

namespace PragmaRX\Version\Package\Support;

use Illuminate\Support\Facades\Cache as IlluminateCache;

trait Cache
{
    /**
     * Add something to the cache.
     *
     * @param $key
     * @param $value
     * @param int $minutes
     */
    protected function cachePut($key, $value, $minutes = 10)
    {
        IlluminateCache::put($key, $value, $this->config('cache.time', $minutes));
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
        return $this->config('cache.enabled')
            ? IlluminateCache::get($key)
            : null;
    }

    /**
     * Get config value.
     *
     * @param $string
     *
     * @return \Illuminate\Config\Repository|mixed
     */
    abstract protected function config($string);

    /**
     * Make the cache key.
     *
     * @param $string
     *
     * @return string
     */
    protected function key($string)
    {
        return $this->config('cache.key').'-'.$string;
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
