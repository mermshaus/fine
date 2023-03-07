<?php

declare(strict_types=1);

namespace mermshaus\fine\Tests;

use mermshaus\fine\Config;
use PHPUnit\Framework\TestCase;

/**
 * @covers \mermshaus\fine\Config
 */
class ConfigTest extends TestCase
{
    public function testCreateInstance(): void
    {
        $obj = new Config();

        static::assertInstanceOf(Config::class, $obj);
    }
}
