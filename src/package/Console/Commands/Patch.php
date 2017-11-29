<?php

namespace PragmaRX\Version\Package\Console\Commands;

class Patch extends Base
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'version:patch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Increment app patch version';

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
        $number = app('pragmarx.version')->incrementPatch();

        $this->info("New patch: {$number}");

        $this->displayAppVersion();
    }
}
