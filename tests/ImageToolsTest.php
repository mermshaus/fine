<?php

namespace mermshaus\fine\Tests;

use mermshaus\fine\ImageTools;
use PHPUnit_Framework_TestCase;

/**
 *
 */
class ImageToolsTest extends PHPUnit_Framework_TestCase
{
    /**
     *
     */
    public function testCreateInstance()
    {
        $obj = new ImageTools();

        $this->assertSame(true, $obj instanceof ImageTools);
    }

    /**
     *
     */
    public function testCreateThumb()
    {
        $obj = new ImageTools();

        $image = imagecreatetruecolor(100, 100);
        $newImage = $obj->createThumb($image, 50, 50);
        $this->assertSame(50, imagesx($newImage));
        $this->assertSame(50, imagesy($newImage));

        // Enlarge
        $image = imagecreatetruecolor(50, 50);
        $newImage = $obj->createThumb($image, 100, 100);
        $this->assertSame(100, imagesx($newImage));
        $this->assertSame(100, imagesy($newImage));
    }

    /**
     *
     */
    public function testScale()
    {
        $obj = new ImageTools();

        $image = imagecreatetruecolor(100, 100);
        $newImage = $obj->scale($image, 50, 50);
        $this->assertSame(50, imagesx($newImage));
        $this->assertSame(50, imagesy($newImage));

        $image = imagecreatetruecolor(100, 100);
        $newImage = $obj->scale($image, 30, 60);
        $this->assertSame(30, imagesx($newImage));
        $this->assertSame(30, imagesy($newImage));

        $image = imagecreatetruecolor(100, 100);
        $newImage = $obj->scale($image, 60, 30);
        $this->assertSame(30, imagesx($newImage));
        $this->assertSame(30, imagesy($newImage));

        $image = imagecreatetruecolor(120, 60);
        $newImage = $obj->scale($image, 60, 20);
        $this->assertSame(40, imagesx($newImage));
        $this->assertSame(20, imagesy($newImage));

        $image = imagecreatetruecolor(50, 50);
        $newImage = $obj->scale($image, 60, 60);
        $this->assertSame(50, imagesx($newImage));
        $this->assertSame(50, imagesy($newImage));

        // Enlarge
        $image = imagecreatetruecolor(25, 50);
        $newImage = $obj->scale($image, 60, 90, true);
        $this->assertSame(45, imagesx($newImage));
        $this->assertSame(90, imagesy($newImage));
    }
}
