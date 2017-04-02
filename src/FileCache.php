<?php

namespace mermshaus\fine;

use SplFileObject;

/**
 *
 */
class FileCache
{
    private $cacheDir;

    public function __construct($cacheDir)
    {
        $this->cacheDir = $cacheDir;

        if (!is_dir($cacheDir) && is_writable(dirname($cacheDir))) {
            mkdir($cacheDir);
        }
    }

    public function saveFromJpegImage($key, $jpegImageResource, $quality)
    {
        if (!is_writable($this->cacheDir)) {
            return false;
        }

        imagejpeg($jpegImageResource, $this->cacheDir . '/' . $key, $quality);

        return true;
    }

    public function hasItem($key)
    {
        return (file_exists($this->cacheDir . '/' . $key));
    }

    public function getItem($key)
    {
        return new FileCacheItem($key, new SplFileObject($this->cacheDir . '/' . $key));
    }
}
