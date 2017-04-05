<?php

namespace mermshaus\fine\Tests;

use mermshaus\fine\ViewScriptManager;
use PHPUnit_Framework_TestCase;

/**
 *
 */
class ViewScriptManagerTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testCreateInstance()
    {
        $obj = new ViewScriptManager();

        $this->assertEquals(true, $obj instanceof ViewScriptManager);
    }

    /**
     *
     */
    public function testAddScript()
    {
        $obj = new ViewScriptManager();

        $obj->addScript('test', function () {});
    }

    /**
     *
     */
    public function testGetScript()
    {
        $obj = new ViewScriptManager();

        $obj->addScript('test', function () { return 'foo'; });

        $closure = $obj->getScript('test');

        $this->assertSame('foo', $closure());
    }

    /**
     *
     */
    public function testGetScriptThrowsException()
    {
        $this->setExpectedException('\\Exception', 'Script not found: "bogus"');

        $obj = new ViewScriptManager();

        $obj->addScript('test', function () { return 'foo'; });

        $obj->getScript('bogus');
    }
}
