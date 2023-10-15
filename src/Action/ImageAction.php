<?php

declare(strict_types=1);

namespace mermshaus\fine\Action;

use mermshaus\fine\ApplicationApi;
use mermshaus\fine\Config;
use mermshaus\fine\FileCache;
use mermshaus\fine\model\FileCacheItem;
use mermshaus\fine\ImageTools;
use mermshaus\fine\model\AbstractViewModel;
use RuntimeException;

final class ImageAction extends AbstractAction
{
    public function __construct(ApplicationApi $api, Config $config, readonly FileCache $cache)
    {
        parent::__construct($api, $config);
    }

    public function execute(): ?AbstractViewModel
    {
        // path, element
        $path = $this->getGetString('path');

        $album = dirname($path);

        if ($album === '.') {
            $album = '';
        }

        // $this->assertValidAlbum($album);

        $basename = basename($path);

        // $this->assertValidFilename($album, $basename);

        $allowedPrefixes = ['thumb', 'large'];

        $prefix = $this->getGetString('element');
        if (!in_array($prefix, $allowedPrefixes, true)) {
            throw new RuntimeException(sprintf('Invalid element: "%s"', $prefix));
        }

        $width = 0;
        $height = 0;
        $quality = 0;

        if ($prefix === 'thumb') {
            $width = $this->config->thumbWidth;
            $height = $this->config->thumbHeight;
            $quality = $this->config->thumbQuality;
        } elseif ($prefix === 'large') {
            $width = $this->config->largeWidth;
            $height = $this->config->largeHeight;
            $quality = $this->config->largeQuality;
        }

        $cacheKey = $this->generateCacheKey($prefix, $album, $basename, $width, $height, $quality);

        if ($this->cache->hasItem($cacheKey)) {
            $cacheItem = $this->cache->getItem($cacheKey);
            $etag = $this->generateETag($cacheItem);

            // ETag
            if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
                header('HTTP/1.1 304 Not Modified');

                return null;
            }

            /* @var $file \SplFileObject */
            $file = $cacheItem->value;

            // Modification date
            if (
                isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
                && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) === $file->getMTime()
            ) {
                header('HTTP/1.1 304 Not Modified');

                return null;
            }

            $lastModified = gmdate('D, d M Y H:i:s \\G\\M\\T', $file->getMTime());
            $this->sendImageHeaders('image/jpeg', $lastModified, $etag, $prefix . '-' . $basename);
            $file->fpassthru();

            return null;
        }

        $imageTools = new ImageTools();

        $localPath = $this->config->albumPath . '/' . $album . '/' . $basename;

        $dstim2 = null;

        if ($prefix === 'thumb') {
            $dstim2 = $imageTools->createThumb($imageTools->loadImage($localPath), $width, $height);
        } elseif ($prefix === 'large') {
            $dstim2 = $imageTools->scale($imageTools->loadImage($localPath), $width, $height);
        }

        if ($this->cache->saveFromJpegImage($cacheKey, $dstim2, $quality)) {
            imagedestroy($dstim2);

            /* @var $file \SplFileObject */
            $cacheItem = $this->cache->getItem($cacheKey);
            $file = $cacheItem->value;

            $lastModified = gmdate('D, d M Y H:i:s \\G\\M\\T', $file->getMTime());
            $this->sendImageHeaders(
                'image/jpeg',
                $lastModified,
                $this->generateETag($cacheItem),
                $prefix . '-' . $basename,
            );
            $file->fpassthru();

            return null;
        }

        $lastModified = gmdate('D, d M Y H:i:s \\G\\M\\T');
        $this->sendImageHeaders('image/jpeg', $lastModified, null, $prefix . '-' . $basename);
        imagejpeg($dstim2, null, $quality);

        return null;
    }

    private function generateETag(FileCacheItem $cacheItem): string
    {
        /* @var $file \SplFileObject */
        $file = $cacheItem->value;

        $mtime = $file->getMTime();

        if ($mtime === false) {
            return '0';
        }

        return (string) $mtime;
    }

    private function sendImageHeaders(
        string $mimeType,
        ?string $lastModified = null,
        ?string $etag = null,
        ?string $filename = null
    ): void {
        header('Content-Type: ' . $mimeType);

        // 30 days
        header('Cache-Control: public, max-age=2592000');

        if ($lastModified !== null && $lastModified !== '') {
            header('Last-Modified: ' . $lastModified);
        }

        if ($etag !== null && $etag !== '') {
            header('ETag: ' . $etag);
        }

        if ($filename !== null && $filename !== '') {
            header('Content-Disposition: inline; filename="' . $filename . '"');
        }
    }
}
