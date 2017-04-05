<?php

namespace mermshaus\fine\Tests;

use mermshaus\fine\Config;
use PHPUnit_Framework_TestCase;

/**
 *
 */
class ConfigTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testCreateInstance()
    {
        $obj = new Config();

        $this->assertSame(true, $obj instanceof Config);
    }

    /**
     *
     */
    public function testDefaultValues()
    {
        $obj = new Config();

        $this->assertSame(realpath(__DIR__ . '/../src'), realpath($obj->albumPath));
        $this->assertSame(realpath(__DIR__ . '/../src/.fine'), realpath($obj->cacheDir));
    }
}
