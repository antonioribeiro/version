<?php

namespace LuanRodrigues\Version\Package\Console\Commands;

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
    protected $description = 'Clear cache and refresh versions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        app('pragmarx.version')->refresh();

        $this->info('Version was refreshed.');

        $this->displayAppVersion();
    }
}
