<?php

namespace PragmaRX\Version\Package;

class Version
{
    const BUILD_CACHE_KEY = 'build';

    /**
     * The config loader.
     *
     * @var \PragmaRX\YamlConf\Package\YamlConf
     */
    protected $config;

    /**
     * @var
     */
    private $cache;

    /**
     * Version constructor.
     */
    public function __construct()
    {
        $this->config = app('pragmarx.yaml-conf');

        $this->cache = app($this->config('cache.manager'));
    }

    private function cache($key, $value)
    {
        $this->cache->put($key, $value);
    }

    private function cached($key)
    {
        if ($this->config('cache.enabled')) {
            return $this->cache->get($key);
        }

        return null;
    }

    /**
     * Get config value.
     *
     * @param $string
     * @return \Illuminate\Config\Repository|mixed
     */
    protected function config($string)
    {
        return config("version.{$string}");
    }

    /**
     * Make the cache key.
     *
     * @param $string
     * @return string
     */
    private function key($string)
    {
        return $this->config('cache.enabled').'-'.$string;
    }

    /**
     * Replace text variables with their values.
     *
     * @param $string
     * @return mixed
     */
    protected function replaceVariables($string)
    {
        return str_replace(
            [
                '{$major}',
                '{$minor}',
                '{$patch}',
                '{$repository}',
                '{$build}',
            ],
            [
                $this->config('current.major'),
                $this->config('current.minor'),
                $this->config('current.patch'),
                $this->config('build.repository'),
                $this->build(),
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
        return $this->replaceVariables($this->makeVersion());
    }

    /**
     * Get the current build.
     *
     * @return mixed
     */
    public function build()
    {
        if (!is_null($value = $this->config('build.value'))) {
            return $value;
        }

        if ($value = $this->cached($key = $this->key(static::BUILD_CACHE_KEY))) {
            return $value;
        }

        $command = str_replace(
            '{$repository}',
            $this->config('build.repository'),
            $this->config('build.command')
        );

        $value = substr(@exec($command), 0, $this->config('build.length'));

        $this->cache($key, $value);

        return $value;
    }

    /**
     * Make version string.
     *
     * @return string
     */
    protected function makeVersion()
    {
        return $this->config('current.format');
    }

    /**
     * Get the current object instance.
     *
     * @return $this
     */
    public function instance()
    {
        return $this;
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

    /**
     * Get a properly formatted version.
     *
     * @param $type
     * @return mixed
     */
    public function format($type)
    {
        return $this->replaceVariables($this->config("format.{$type}"));
    }
}
