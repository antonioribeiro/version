<?php

namespace PragmaRX\Version\Package\Console\Commands;

use Illuminate\Console\Command;

class Base extends Command
{
    /**
     * Draw a line in console.
     *
     * @param int $len
     */
    public function drawLine($len = 80)
    {
        if (is_string($len)) {
            $len = strlen($len);
        }

        $this->line(str_repeat('-', max($len, 80)));
    }

    /**
     * Display the current app version.
     *
     * @param string $format
     */
    public function displayAppVersion($format = 'full')
    {
        $this->info(config('app.name').' '.app('pragmarx.version')->format($format));
    }
}
