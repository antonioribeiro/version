<?php

namespace PragmaRX\Version\Package\Console\Commands;

class Minor extends Base
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'version:minor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Increment app minor version';

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
        $number = app('pragmarx.version')->incrementMinor();

        $this->info("New minor version: {$number}");

        $this->displayAppVersion();
    }
}
