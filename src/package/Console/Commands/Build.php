<?php

namespace PragmaRX\Version\Package\Console\Commands;

class Build extends Base
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'version:build';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Increment app build number';

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
        $build = app('pragmarx.version')->incrementBuild();

        $this->info("New build: {$build}");

        $this->info(config('app.name') . ' ' . app('pragmarx.version')->format('full'));
    }
}
