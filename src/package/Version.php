<?php

namespace PragmaRX\Version\Package;

use Illuminate\Support\Str;
use PragmaRX\Version\Package\Exceptions\MethodNotFound;
use PragmaRX\Version\Package\Support\Absorb;
use PragmaRX\Version\Package\Support\Config;
use PragmaRX\Version\Package\Support\Constants;
use PragmaRX\Version\Package\Support\Git;
use PragmaRX\Version\Package\Support\Increment;
use PragmaRX\Version\Package\Support\Timestamp;
use PragmaRX\Yaml\Package\Yaml;

class Version
{
    /**
     * @var \PragmaRX\Yaml\Package\Yaml
     */
    protected $yaml;

    /**
     * @var \PragmaRX\Version\Package\Support\Config
     */
    protected $config;

    /**
     * @var \PragmaRX\Version\Package\Support\Git
     */
    protected $git;

    /**
     * @var \PragmaRX\Version\Package\Support\Increment
     */
    protected $increment;

    /**
     * @var Absorb
     */
    private $absorb;

    /**
     * @var Timestamp
     */
    private $timestamp;

    /**
     * Version constructor.
     *
     * @param Config|null    $config
     * @param Git|null       $git
     * @param Increment|null $increment
     * @param Yaml           $yaml
     * @param Absorb|null    $absorb
     */
    public function __construct(
        Config $config = null,
        Git $git = null,
        Increment $increment = null,
        Yaml $yaml = null,
        Absorb $absorb = null,
        Timestamp $timestamp = null
    ) {
        $this->instantiate($config, $git, $increment, $yaml, $absorb, $timestamp);
    }

    /**
     * Dynamically call format types.
     *
     * @param $name
     * @param array $arguments
     *
     * @throws MethodNotFound
     *
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        if (Str::startsWith($name, 'increment')) {
            return $this->increment->$name(...$arguments);
        }

        if (Str::startsWith($name, 'timestamp')) {
            return $this->timestamp->$name(...$arguments);
        }

        if (Str::startsWith($name, 'absorb')) {
            return $this->absorb->$name(...$arguments);
        }

        if (!is_null($version = $this->format($name))) {
            return $version;
        }

        throw new MethodNotFound(
            "Method '{$name}' doesn't exists in this object."
        );
    }

    /**
     * Get a version.
     *
     * @param $type
     *
     * @return string
     */
    protected function getCurrent($type)
    {
        return $this->config->has("current.{$type}") ? ($this->config->get("current.{$type}") ?? '') : null;
    }

    /**
     * Get a version.
     *
     * @param $type
     *
     * @return string
     */
    public function getGit()
    {
        return $this->git;
    }

    /**
     * Instantiate all dependencies.
     *
     * @param $config
     * @param $git
     * @param $increment
     * @param $yaml
     */
    protected function instantiate(
        $config,
        $git,
        $increment,
        $yaml,
        $absorb,
        $timestamp
    ) {
        $yaml = $this->instantiateClass($yaml ?: app('pragmarx.yaml'), 'yaml');

        $config = $this->instantiateClass($config, 'config', Config::class, [
            $yaml,
        ]);

        $git = $this->instantiateClass($git, 'git', Git::class, [
            $config,
        ]);

        $this->instantiateClass($increment, 'increment', Increment::class, [
            $config,
        ]);

        $timestamp = $this->instantiateClass($increment, 'timestamp', Timestamp::class, [
            $config,
        ]);

        $this->instantiateClass($absorb, 'absorb', Absorb::class, [
            $config,
            $git,
            $timestamp,
            $this,
        ]);
    }

    /**
     * Instantiate a class.
     *
     * @param $instance  object
     * @param $property  string
     * @param $class     string
     *
     * @return Yaml|object
     */
    protected function instantiateClass(
        $instance,
        $property,
        $class = null,
        $arguments = []
    ) {
        return $this->{$property} = is_null($instance)
            ? ($instance = new $class(...$arguments))
            : $instance;
    }

    /**
     * Replace text variables with their values.
     *
     * @param $string
     *
     * @return mixed
     */
    protected function replaceVariables($string)
    {
        do {
            $original = $string;

            $string = $this->searchAndReplaceVariables($string);
        } while ($original !== $string);

        return $string;
    }

    /**
     * Search and replace variables ({$var}) in a string.
     *
     * @param $string
     *
     * @return mixed
     */
    protected function searchAndReplaceVariables($string)
    {
        while (preg_match('/(\{\$(.*)\})/U', $string, $matches)) {
            $old = $string;

            if (!is_null($value = $this->getCurrent($matches[2]))) {
                $string = str_replace($matches[0], $value, $string);
            }

            if ($format = $this->config->get('format.'.$matches[2])) {
                $string = str_replace($matches[0], $format, $string);
            }

            if ($old !== $string) {
                return $this->searchAndReplaceVariables($string);
            }

            break;
        }

        while (preg_match('/'.$this->config->get('format.regex.optional_bracket').'/', $string, $matches)) {
            if (count($matches) > 2) {
                $string = str_replace($matches[0], trim($matches['optional']) ? $matches['prefix'].$matches['spaces'].$matches['optional'] : '', $string);
            } else {
                break;
            }
        }

        return $string;
    }

    /**
     * Get the current version.
     *
     * @return string
     */
    public function current()
    {
        return $this->replaceVariables($this->config->get('format.version'));
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
     * Get a properly formatted version.
     *
     * @param $type
     *
     * @return mixed|null
     */
    public function format($type = null)
    {
        $type = $type ?: Constants::DEFAULT_FORMAT;

        if (!is_null($value = $this->config->get("format.{$type}"))) {
            return $this->replaceVariables($value);
        }
    }

    /**
     * Is it in absorb mode?
     *
     * @param $type
     *
     * @return bool
     */
    public function isInAbsorbMode()
    {
        return $this->isVersionInAbsorbMode() ||
            $this->isBuildInAbsorbMode() ||
            $this->isVersionInAbsorbMode();
    }

    /**
     * Is version in absorb mode?
     *
     * @param $type
     *
     * @return bool
     */
    public function isVersionInAbsorbMode()
    {
        return $this->config->get('mode') == Constants::MODE_ABSORB;
    }

    /**
     * Is build in absorb mode?
     *
     * @param $type
     *
     * @return bool
     */
    public function isBuildInAbsorbMode()
    {
        return $this->config->get('commit.mode') == Constants::MODE_ABSORB;
    }

    /**
     * Is timestamp in absorb mode?
     *
     * @param $type
     *
     * @return bool
     */
    public function isTimestampInAbsorbMode()
    {
        return $this->config->get('current.timestamp.mode') == Constants::MODE_ABSORB;
    }

    /**
     * Set the config file stub.
     *
     * @param string $configFileStub
     */
    public function setConfigFileStub($configFileStub)
    {
        $this->config->setConfigFileStub($configFileStub);
    }

    /**
     * Load package YAML configuration.
     *
     * @param $path
     *
     * @return \Illuminate\Support\Collection
     */
    public function loadConfig($path = null)
    {
        return $this->config->loadConfig($path);
    }
}
