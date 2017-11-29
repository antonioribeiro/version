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
        IlluminateCache::put($key, $value, $minutes);
    }

    /**
     * Retrieve something from cache.
     *
     * @param $key
     *
     * @return null
     */
    protected function cacheGet($key)
    {
        if ($this->config('cache.enabled')) {
            return IlluminateCache::get($key);
        }
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
        return $this->config('cache.enabled').'-'.$string;
    }

    /**
     * Get the current object instance.
     */
    public function clearCache()
    {
        IlluminateCache::forget($this->key(static::BUILD_CACHE_KEY));
    }

    /**
     * Get the current object instance.
     */
    public function refreshBuild()
    {
        $this->clearCache();

        return $this->build();
    }
}
