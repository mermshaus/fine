<?php

declare(strict_types=1);

namespace mermshaus\fine;

use GdImage;
use LogicException;
use mermshaus\fine\model\FileCacheItem;
use RuntimeException;
use SplFileObject;

final class FileCache
{
    private string $cacheDir;

    public function __construct(string $cacheDir)
    {
        $this->cacheDir = $cacheDir;

        if (!is_dir($cacheDir) && is_writable(dirname($cacheDir))) {
            mkdir($cacheDir);
        }
    }

    public function isWritable(): bool
    {
        return is_writable($this->cacheDir);
    }

    public function saveFromJpegImage(string $key, GdImage $imageResource, int $quality): bool
    {
        if (!is_writable($this->cacheDir)) {
            return false;
        }

        imagejpeg($imageResource, $this->cacheDir . '/' . $key, $quality);

        return true;
    }

    /**
     * Delete files in cache that aren't managed by the application (e.g.
     * because of deprecated prefixes).
     *
     * @throws RuntimeException
     */
    public function clearUnmanagedItems(array $managedCachePrefixes): int
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
                // continue;
            }
        }

        return $deleteCounter;
    }

    /**
     * @throws RuntimeException
     */
    public function clearItemsNotInList(string $prefix, array $keysHashMap): int
    {
        $deleteCounter = 0;

        foreach (glob($this->cacheDir . '/' . $prefix . ',*.cache') as $file) {
            $pathinfo = pathinfo($file);

            if (!isset($keysHashMap[$pathinfo['basename']])) {
                if ($pathinfo['extension'] !== 'cache') {
                    throw new RuntimeException('Found cache file with invalid file extension');
                }
                $this->deleteItem($pathinfo['basename']);
                $deleteCounter++;
            }
        }

        return $deleteCounter;
    }

    /**
     * @throws RuntimeException
     */
    public function deleteItem(string $key): void
    {
        if (!$this->hasItem($key)) {
            throw new RuntimeException(
                sprintf('Trying to delete cache item "%s", but no such item exists in cache', $key),
            );
        }

        $filepath = $this->cacheDir . '/' . $key;

        unlink($filepath);
    }

    public function hasItem(string $key): bool
    {
        return file_exists($this->cacheDir . '/' . $key);
    }

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
    public function getItem(string $key): FileCacheItem
    {
        return new FileCacheItem($key, new SplFileObject($this->cacheDir . '/' . $key));
    }
}
