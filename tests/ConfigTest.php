<?php

namespace mermshaus\fine\Tests;

use mermshaus\fine\Config;
use PHPUnit_Framework_TestCase;

class ConfigTest extends PHPUnit_Framework_TestCase
{
    /**
     * @throws \PHPUnit_Framework_Exception
     */
    public function testCreateInstance()
    {
        $obj = new Config();

        static::assertInstanceOf('\\mermshaus\\fine\\Config', $obj);
    }

    /**
     *
     */
    public function testDefaultValues()
    {
        $obj = new Config();

        #static::assertSame(realpath(__DIR__ . '/../src'), realpath($obj->albumPath));
        #static::assertSame(realpath(__DIR__ . '/../src/.fine'), realpath($obj->cacheDir));
    }
}
