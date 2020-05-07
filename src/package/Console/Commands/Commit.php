<?php

namespace PragmaRX\Version\Package\Console\Commands;

class Commit extends Base
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'version:commit {--increment-by= : Increment by a different amount than config }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Increment app commit number';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->checkIfCanIncrement('current', 'build')) {
            $commit = app('pragmarx.version')->incrementCommit(
                $this->option('increment-by')
            );

            $this->info("New commit: {$commit}");

            $this->displayAppVersion();
        }
    }
}
