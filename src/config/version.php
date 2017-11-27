<?php

return [

    'version' => [
        'major' => '1',

        'minor' => '0',

        'patch' => '0',
    ],

    'git' => [
        'enabled' => true,

        'command' => 'git ls-remote %s refs/heads/master',

        'repository' => env('APP_GIT_REPOSITORY'),
    ],

    'display' => [
        'full' => 'version {$version} (build {$commit})',

        'compact' => 'v. {$major}.{$major}.{$build}',
    ],

];
