<?php

namespace PragmaRX\Version\Package\Console\Commands;

class Timestamp extends Base
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'version:timestamp';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Write the current timestamp to config';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->checkIfCanIncrement('current', 'timestamp')) {
            $number = app('pragmarx.version')->timestampToConfig();

            $this->info("New timestamp: {$number}");

            $this->displayAppVersion();
        }
    }
}
