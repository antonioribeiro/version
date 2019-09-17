<?php

namespace PragmaRX\Version\Package\Support;

use Carbon\Carbon;

class Timestamp
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
    public function timestampToConfig()
    {
        $config = $this->config->getRoot();

        $config['current']['timestamp'] = $this->explode($timestamp = Carbon::now());

        $this->config->update($config);

        event(Constants::EVENT_TIMESTAMP_UPDATED);

        return (string) $timestamp;
    }

    public function explode($date)
    {
        return [
            'year'     => $date->year,
            'month'    => $date->month,
            'day'      => $date->day,
            'hour'     => $date->hour,
            'minute'   => $date->minute,
            'second'   => $date->second,
            'timezone' => $date->timezone->getName(),
        ];
    }
}
