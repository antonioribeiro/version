<?php

namespace PragmaRX\Version\Package\Support;

class Constants
{
    const MODE_INCREMENT = 'increment';

    const MODE_ABSORB = 'absorb';

    const VERSION_SOURCE_CONFIG = 'config';

    const VERSION_CACHE_KEY = 'version';

    const COMMIT_CACHE_KEY = 'commit';

    const DEFAULT_FORMAT = 'full';

    const VERSION_SOURCE_GIT_LOCAL = 'git-local';

    const VERSION_SOURCE_GIT_REMOTE = 'git-remote';

    const EVENT_VERSION_ABSORBED = 'pragmarx:version:events:version-absorbed';

    const EVENT_COMMIT_INCREMENTED = 'pragmarx:version:events:commit-incremented';

    const EVENT_MAJOR_INCREMENTED = 'pragmarx:version:events:major-incremented';

    const EVENT_MINOR_INCREMENTED = 'pragmarx:version:events:minor-incremented';

    const EVENT_PATCH_INCREMENTED = 'pragmarx:version:events:patch-incremented';

    const EVENT_TIMESTAMP_UPDATED = 'pragmarx:version:events:timestamp-updated';
}
