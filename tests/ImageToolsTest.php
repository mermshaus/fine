<?php

namespace mermshaus\fine\Tests;

use mermshaus\fine\ImageTools;
use PHPUnit_Framework_TestCase;

class ImageToolsTest extends PHPUnit_Framework_TestCase
{
    /**
     * @throws \PHPUnit_Framework_Exception
     */
    public function testCreateInstance()
    {
        $obj = new ImageTools();

        static::assertInstanceOf('\\mermshaus\\fine\\ImageTools', $obj);
    }

    public function testCreateThumb()
    {
        $obj = new ImageTools();

        $image    = imagecreatetruecolor(100, 100);
        $newImage = $obj->createThumb($image, 50, 50);
        static::assertSame(50, imagesx($newImage));
        static::assertSame(50, imagesy($newImage));

        // Enlarge
        $image    = imagecreatetruecolor(50, 50);
        $newImage = $obj->createThumb($image, 100, 100);
        static::assertSame(100, imagesx($newImage));
        static::assertSame(100, imagesy($newImage));
    }

    public function testScale()
    {
        $obj = new ImageTools();

        $image    = imagecreatetruecolor(100, 100);
        $newImage = $obj->scale($image, 50, 50);
        static::assertSame(50, imagesx($newImage));
        static::assertSame(50, imagesy($newImage));

        $image    = imagecreatetruecolor(100, 100);
        $newImage = $obj->scale($image, 30, 60);
        static::assertSame(30, imagesx($newImage));
        static::assertSame(30, imagesy($newImage));

        $image    = imagecreatetruecolor(100, 100);
        $newImage = $obj->scale($image, 60, 30);
        static::assertSame(30, imagesx($newImage));
        static::assertSame(30, imagesy($newImage));

        $image    = imagecreatetruecolor(120, 60);
        $newImage = $obj->scale($image, 60, 20);
        static::assertSame(40, imagesx($newImage));
        static::assertSame(20, imagesy($newImage));

        $image    = imagecreatetruecolor(50, 50);
        $newImage = $obj->scale($image, 60, 60);
        static::assertSame(50, imagesx($newImage));
        static::assertSame(50, imagesy($newImage));

        // Enlarge
        $image    = imagecreatetruecolor(25, 50);
        $newImage = $obj->scale($image, 60, 90, true);
        static::assertSame(45, imagesx($newImage));
        static::assertSame(90, imagesy($newImage));
    }
}
