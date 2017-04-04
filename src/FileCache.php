<?php

namespace mermshaus\fine;

use Exception;
use SplFileObject;

/**
 *
 */
class FileCache
{
    /**
     *
     * @var string
     */
    private $cacheDir;

    /**
     *
     * @param string $cacheDir
     */
    public function __construct($cacheDir)
    {
        $this->cacheDir = $cacheDir;

        if (!is_dir($cacheDir) && is_writable(dirname($cacheDir))) {
            mkdir($cacheDir);
        }
    }

    /**
     *
     * @return bool
     */
    public function isWritable()
    {
        return (is_writable($this->cacheDir));
    }

    /**
     *
     * @param string $key
     * @param resource $imageResource
     * @param int $quality
     * @return boolean
     */
    public function saveFromJpegImage($key, $imageResource, $quality)
    {
        if (!is_writable($this->cacheDir)) {
            return false;
        }

        imagejpeg($imageResource, $this->cacheDir . '/' . $key, $quality);

        return true;
    }

    /**
     * Delete files in cache that aren't managed by the application (e. g.
     * because of deprecated prefixes)
     *
     * @param array $managedCachePrefixes
     * @return int
     */
    public function clearUnmanagedItems(array $managedCachePrefixes)
    {
        $deleteCounter = 0;

        foreach (glob($this->cacheDir . '/*') as $filepath) {
            $pathinfo = pathinfo($filepath);

            if (is_dir($filepath)) {
                // Skip directories for now
                continue;
            }

            if (!isset($pathinfo['extension']) || $pathinfo['extension'] !== 'cache') {
                $this->deleteItem($pathinfo['basename']);
                $deleteCounter++;
                continue;
            }

            $parts = explode(',', $pathinfo['basename']);

            if (!in_array($parts[0], $managedCachePrefixes, true)) {
                $this->deleteItem($pathinfo['basename']);
                $deleteCounter++;
                continue;
            }
        }

        return $deleteCounter;
    }

    /**
     *
     * @param string $prefix
     * @param array $keysHashMap
     * @return int
     * @throws Exception
     */
    public function clearItemsNotInList($prefix, array $keysHashMap)
    {
        $deleteCounter = 0;

        foreach (glob($this->cacheDir . '/' . $prefix . ',*.cache') as $file) {
            $pathinfo = pathinfo($file);

            if (!isset($keysHashMap[$pathinfo['basename']])) {
                if ($pathinfo['extension'] !== 'cache') {
                    throw new Exception();
                }
                $this->deleteItem($pathinfo['basename']);
                $deleteCounter++;
            }
        }

        return $deleteCounter;
    }

    /**
     *
     * @param string $key
     * @throws Exception
     */
    public function deleteItem($key)
    {
        if (!$this->hasItem($key)) {
            throw new Exception(sprintf('Trying to delete cache item "%s", but no such item exists in cache', $key));
        }

        $filepath = $this->cacheDir . '/' . $key;

        unlink($filepath);
    }

    /**
     *
     * @param string $key
     * @return bool
     */
    public function hasItem($key)
    {
        return (file_exists($this->cacheDir . '/' . $key));
    }

    /**
     *
     * @param string $key
     * @return FileCacheItem
     */
    public function getItem($key)
    {
        return new FileCacheItem($key, new SplFileObject($this->cacheDir . '/' . $key));
    }
}
