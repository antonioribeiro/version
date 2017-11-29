<?php

namespace PragmaRX\Version\Package\Console\Commands;

use PragmaRX\Version\Package\Version;

class Refresh extends Base
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'version:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear build cache and refresh it';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (config('version.build.mode') === Version::BUILD_MODE_NUMBER) {
            $this->info('You are using the "number" build mode, which does not require you to refresh it.');

            return;
        }

        app('pragmarx.version')->refreshBuild();

        $this->info('App build was refreshed.');

        $this->displayAppVersion();
    }
}
