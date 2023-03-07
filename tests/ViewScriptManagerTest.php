<?php

declare(strict_types=1);

namespace mermshaus\fine\Tests;

use mermshaus\fine\ViewScriptManager;
use PHPUnit\Framework\TestCase;

/**
 * @covers \mermshaus\fine\ViewScriptManager
 */
class ViewScriptManagerTest extends TestCase
{
    public function testCreateInstance(): void
    {
        $obj = new ViewScriptManager();

        static::assertInstanceOf(ViewScriptManager::class, $obj);
    }

    public function testGetScript(): void
    {
        $obj = new ViewScriptManager();

        $obj->addScript('test', function () {
            return 'foo';
        });

        $closure = $obj->getScript('test');

        $this->assertSame('foo', $closure());
    }

    public function testGetScriptThrowsException(): void
    {
        $this->expectExceptionMessage('Script not found: "bogus"');

        $obj = new ViewScriptManager();

        $obj->addScript('test', function () {
            return 'foo';
        });

        $obj->getScript('bogus');
    }
}
