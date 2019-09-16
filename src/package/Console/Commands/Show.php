<?php

namespace PragmaRX\Version\Package\Console\Commands;

class Show extends Base
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'version:show {--format= : Use a different format (default: full)} {--suppress-app-name : Do not include the app name in the version}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show current app version and commit';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $format = $this->option('format') ?: 'full';

        $appName = $this->option('suppress-app-name')
            ? ''
            : config('app.name').' ';

        $this->info($appName.app('pragmarx.version')->format($format));
    }
}
