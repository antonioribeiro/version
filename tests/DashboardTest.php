<?php

namespace PragmaRX\Version\Tests;

use PragmaRX\Version\Package\Facade as VersionFacade;
use PragmaRX\Version\Package\Version as VersionService;

class VersionTest extends TestCase
{
    /**
     * @var Version
     */
    private $version;

    const currentVersion = '1.0.0';

    public function setUp()
    {
        parent::setup();

        $this->version = VersionFacade::instance();
    }

    public function test_can_instantiate_service()
    {
        $this->assertInstanceOf(VersionService::class, $this->version);
    }

    public function test_can_get_version()
    {
        $this->assertEquals(static::currentVersion, $this->version->version());
    }
}
