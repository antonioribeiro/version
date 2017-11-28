<?php

namespace PragmaRX\Version\Package\Support;

trait Cache
{
    /**
     * Cache manager instance.
     *
     * @var
     */
    protected $cache;

    /**
     * Add something to the cache.
     *
     * @param $key
     * @param $value
     * @param int $minutes
     */
    protected function cachePut($key, $value, $minutes = 10)
    {
        $this->cache->put($key, $value, $minutes);
    }

    /**
     * Retrieve something from cache.
     *
     * @param $key
     * @return null
     */
    protected function cacheGet($key)
    {
        if ($this->config('cache.enabled')) {
            return $this->cache->get($key);
        }

        return null;
    }

    /**
     * Make the cache key.
     *
     * @param $string
     * @return string
     */
    protected function key($string)
    {
        return $this->config('cache.enabled') . '-' . $string;
    }

    /**
     * Get the current object instance.
     */
    public function clearCache()
    {
        $this->cache->forget($this->key(static::BUILD_CACHE_KEY));
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
