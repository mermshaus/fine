<?php

namespace mermshaus\fine;

use mermshaus\fine\model;

final class Application
{
    /**
     * Version number of the application (uses Semantic Versioning)
     */
    const VERSION = '0.6.0-dev';

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ViewScriptManager
     */
    private $viewScriptManager;

    /**
     * @var FileCache
     */
    private $cache;

    /**
     * @var ApplicationApi
     */
    private $api;

    /**
     * @param Config            $config
     * @param ViewScriptManager $viewScriptManager
     * @param FileCache         $cache
     */
    public function __construct(
        Config $config,
        ViewScriptManager $viewScriptManager,
        FileCache $cache
    ) {
        $this->config = $config;

        $this->viewScriptManager = $viewScriptManager;

        $this->cache = $cache;

        // We're not using a type hint here in order to support both PHP 5/7 Exceptions and PHP 7 Throwables
        set_exception_handler(function ($e) {
            /** @var \Exception|\Throwable $e */
            header('HTTP/1.1 404 Not Found');
            header('Content-Type: text/plain; charset=UTF-8');
            echo $e->getMessage() . "\n";
        });
    }

    private function getApi()
    {
        if ($this->api === null) {
            $this->api = new ApplicationApi($this);
        }

        return $this->api;
    }

    /**
     *
     * @return ViewScriptManager
     */
    public function getViewScriptManager()
    {
        return $this->viewScriptManager;
    }

    /**
     *
     * @return string
     */
    public function getVersion()
    {
        return self::VERSION;
    }

    /**
     *
     * @param string $url
     */
    private function doRedirect($url)
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     *
     * @return bool
     */
    private function isInSingleAlbumMode()
    {
        return $this->config->singleAlbumMode;
    }

    /**
     *
     * @param string $action
     * @param array  $params
     *
     * @return string
     */
    public function url($action, array $params = [])
    {
        if (isset($params['album']) && $params['album'] === '' && in_array($action, ['album', 'detail', 'image'])) {
            unset($params['album']);
        }

        $url = './';

        if (basename(__FILE__) !== 'index.php') {
            $url = basename(__FILE__);
        }

        if ($action !== 'index') {
            $params = array_merge(['action' => $action], $params);
        }

        if (count($params) > 0) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    /**
     * @param string $file
     *
     * @return model\Image
     * @throws \RuntimeException
     */
    private function loadImage($file)
    {
        if (!is_readable($file)) {
            throw new \RuntimeException(sprintf('File %s is not readable.', basename($file)));
        }

        return new model\Image($file);
    }

    /**
     * @param string $path
     *
     * @return model\Image[]
     * @throws \LogicException
     * @throws \RuntimeException
     */
    private function getImages($path)
    {
        $files = glob($path . '/*.{JPG,jpg,JPEG,jpeg,PNG,png,GIF,gif}', GLOB_BRACE);

        $images = [];

        foreach ($files as $file) {
            $images[] = $this->loadImage($file);
        }

        // Sorts by creation date (desc) and filename (asc)
        usort($images, function (model\Image $a, model\Image $b) {
            /*if ($a->sortDate < $b->sortDate) {
                return 1;
            } elseif ($a->sortDate > $b->sortDate) {
                return -1;
            }*/

            if ($a->getBasename() < $b->getBasename()) {
                return -1;
            }

            if ($a->getBasename() > $b->getBasename()) {
                return 1;
            }

            throw new \LogicException('Failed sorting images');
        });

        return $images;
    }

    /**
     * @return array
     */
    private function getAlbums()
    {
        static $albums = null;

        if ($albums !== null) {
            return $albums;
        }

        $albums = [];

        foreach (glob($this->config->albumPath . '/*', GLOB_ONLYDIR) as $p) {
            $tmp = basename($p);

            if ($tmp !== '.fine') {
                $albums[] = $tmp;
            }
        }

        usort($albums, function ($a, $b) {
            return strcasecmp($a, $b);
        });

        return $albums;
    }

    /**
     * @throws \RuntimeException
     */
    public function run()
    {
        $action = $this->getGetString('action', 'index');

        $methodName = $action . 'Action';

        if (!method_exists($this, $methodName)) {
            throw new \RuntimeException(sprintf('Unknown action: "%s"', $action));
        }

        $ret = $this->$methodName();

        if ($ret instanceof View) {
            header('Content-Type: text/html; charset=utf-8');

            $layout = new View($this->getApi(), 'layout');

            $layout->contentView = $ret;
            $layout->html_id     = 'page-' . $action;
            $layout->appVersion  = $this->getVersion();

            $layout->output();
        }
    }

    /**
     * @return View
     * @throws \LogicException
     * @throws \RuntimeException
     */
    private function statusAction() // (no parameters)
    {
        $prefixes = ['thumb', 'large'];

        $ret = '';

        $ret .= 'Cache is writable: ' . ($this->canUseCache() ? 'yes' : '**NO**') . "\n\n";
        $ret .= 'Cache prefixes: ' . implode(', ', $prefixes) . "\n\n";

        // Clear unmanaged items

        $clearedItemsCount = $this->cache->clearUnmanagedItems($prefixes);

        $ret .= 'Deleted unaccounted files: ' . $clearedItemsCount . "\n\n";

        // Clear orphaned items

        $info = [];

        $albums = $this->isInSingleAlbumMode() ? [''] : $this->getAlbums();

        $first = true;

        foreach ($prefixes as $prefix) {
            $keysHashMap = [];

            foreach ($albums as $album) {
                if (!isset($info[$album])) {
                    $info[$album] = [
                        'count' => 0,
                        'size'  => 0,
                        'cache' => [],
                    ];
                }

                if (!isset($info[$album]['cache'][$prefix])) {
                    $info[$album]['cache'][$prefix] = [
                        'count' => 0,
                        'size'  => 0,
                    ];
                }

                $images = $this->getImages($this->config->albumPath . '/' . $album);

                foreach ($images as $image) {
                    if ($prefix === 'thumb') {
                        $width   = $this->config->thumbWidth;
                        $height  = $this->config->thumbHeight;
                        $quality = $this->config->thumbQuality;
                    } elseif ($prefix === 'large') {
                        $width   = $this->config->largeWidth;
                        $height  = $this->config->largeHeight;
                        $quality = $this->config->largeQuality;
                    } else {
                        throw new \RuntimeException(sprintf('Unknown prefix "%s" in status action', $prefix));
                    }

                    $cacheKey = $this->generateCacheKey(
                        $prefix,
                        $album,
                        $image->getBasename(),
                        $width,
                        $height,
                        $quality
                    );

                    if ($this->cache->hasItem($cacheKey)) {
                        $keysHashMap[$cacheKey] = true;
                        $info[$album]['cache'][$prefix]['count']++;

                        $info[$album]['cache'][$prefix]['size'] += $this->cache->getItem($cacheKey)->get()->getSize();
                    }

                    if ($first) {
                        $info[$album]['count']++;
                        $info[$album]['size'] += $image->getFileSize();
                    }
                }
            }

            $clearedOrphanedItemsCount = $this->cache->clearItemsNotInList($prefix, $keysHashMap);

            $ret .= $prefix . ': orphaned elements deleted from cache: ' . $clearedOrphanedItemsCount . "\n";

            $first = false;
        }

        $view = new View($this->getApi(), 'status');

        $view->prefixes = $prefixes;
        $view->output   = $ret;
        $view->info     = $info;

        return $view;
    }

    /**
     * @staticvar array $jsonStore
     *
     * @param string $resourceKey
     *
     * @return array
     * @throws \RuntimeException
     */
    private function loadResource($resourceKey)
    {
        static $jsonStore = null;

        if ($jsonStore === null) {
            $handle = fopen(__FILE__, 'rb');
            fseek($handle, __COMPILER_HALT_OFFSET__);
            $json = stream_get_contents($handle);
            fclose($handle);

            $jsonStore = json_decode($json, true);
        }

        if (!array_key_exists($resourceKey, $jsonStore)) {
            throw new \RuntimeException(sprintf('Unknown resource file "%s"', $resourceKey));
        }

        return $jsonStore[$resourceKey];
    }

    /**
     * @throws \RuntimeException
     */
    private function assetAction() // file
    {
        $file = $this->getGetString('file', '');

        $mtime = filemtime(__FILE__);

        // ETag
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === md5($mtime . '-' . $file)) {
            header('HTTP/1.1 304 Not Modified');
            return;
        }

        // Modification date
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) === $mtime) {
            header('HTTP/1.1 304 Not Modified');
            return;
        }

        $data = $this->loadResource($file);

        header('Content-Type: ' . $data['type'] . '; charset=UTF-8');
        header('ETag: ' . md5($mtime . '-' . $file));

        header('Cache-Control: public, max-age=2592000'); // 30 days

        $lastModified = gmdate('D, d M Y H:i:s \\G\\M\\T', $mtime);

        header('Last-Modified: ' . $lastModified);

        echo $data['content'];
    }

    /**
     * @param $album
     *
     * @throws \LogicException
     * @throws \RuntimeException
     */
    private function assertValidAlbum($album)
    {
        if (!is_string($album)) {
            throw new \LogicException('$album must be of type string');
        }

        if ($album === '' && $this->isInSingleAlbumMode()) {
            return;
        }

        if (!in_array($album, $this->getAlbums(), true)) {
            throw new \RuntimeException(sprintf('Unknown album "%s"', $album));
        }
    }

    /**
     * @param string $album
     * @param string $filename
     *
     * @throws \LogicException
     * @throws \RuntimeException
     */
    private function assertValidFilename($album, $filename)
    {
        if (!is_string($album)) {
            throw new \LogicException('$album must be of type string');
        }

        if (!is_string($filename)) {
            throw new \LogicException('$filename must be of type string');
        }

        $this->assertValidAlbum($album);

        $normalizedAlbumPath = realpath($this->config->albumPath . '/' . $album);

        if ($normalizedAlbumPath === false) {
            throw new \LogicException('Album path is empty.');
        }

        $normalizedFilePath = realpath($normalizedAlbumPath . '/' . $filename);

        if ($normalizedFilePath === false) {
            throw new \RuntimeException('File does not exist.');
        }

        if ($normalizedFilePath !== $normalizedAlbumPath . '/' . $filename) {
            throw new \RuntimeException('Filename is not normalized.');
        }

        if ($normalizedAlbumPath !== pathinfo($normalizedFilePath, PATHINFO_DIRNAME)) {
            throw new \LogicException('This should never happen.');
        }
    }

    /**
     * @return View
     * @throws \LogicException
     * @throws \RuntimeException
     */
    private function detailAction() // album, filename
    {
        $album = $this->getGetString('album');
        $this->assertValidAlbum($album);

        $filename = $this->getGetString('filename');
        $this->assertValidFilename($album, $filename);

        $imageUrl = $this->url('image', ['album' => $album, 'filename' => $filename, 'element' => 'large']);

        $albumPath = $this->config->albumPath . '/' . $album;

        $images = $this->getImages($albumPath);

        $i = 0;
        foreach ($images as $image) {
            if ($image->getBasename() === $filename) {
                break;
            }
            $i++;
        }

        $page = 1;

        while ($i >= $page * $this->config->imagesPerPage) {
            $page++;
        }

        $prevImageUrl = '';
        $nextImageUrl = '';

        if (count($images) > 1) {
            if ($i - 1 >= 0) {
                $prevImageUrl = $this->url('detail', ['album' => $album, 'filename' => $images[$i - 1]->getBasename()]);
            } else {
                $prevImageUrl = $this->url('detail', ['album' => $album, 'filename' => $images[count($images) - 1]->getBasename()]);
            }

            if ($i + 1 < count($images)) {
                $nextImageUrl = $this->url('detail', ['album' => $album, 'filename' => $images[$i + 1]->getBasename()]);
            } else {
                $nextImageUrl = $this->url('detail', ['album' => $album, 'filename' => $images[0]->getBasename()]);
            }
        }

        $view = new View($this->getApi(), 'detail');

        $view->album        = $album;
        $view->i            = $i;
        $view->imageUrl     = $imageUrl;
        $view->images       = $images;
        $view->image        = $image;
        $view->prevImageUrl = $prevImageUrl;
        $view->nextImageUrl = $nextImageUrl;
        $view->page         = $page;
        $view->filename     = $filename;

        return $view;
    }

    /**
     * @throws \LogicException
     * @throws \RuntimeException
     */
    private function albumAction() // album, page
    {
        $album = $this->getGetString('album');
        $this->assertValidAlbum($album);

        $activePage = $this->getGetString('page', '1');
        if (preg_match('/\A[1-9][0-9]*\z/', $activePage) === 0) {
            throw new \RuntimeException('Value for page parameter must be >= 1.');
        }
        $activePage = (int) $activePage;

        $images = $this->getImages($this->config->albumPath . '/' . $album);

        $imagesCount = count($images);

        $images = array_slice($images, ($activePage - 1) * $this->config->imagesPerPage, $this->config->imagesPerPage);

        if (count($images) === 0) {
            throw new \RuntimeException('No data for given parameters.');
        }

        $pagesCount = 0;

        do {
            $pagesCount++;
        } while ($pagesCount * $this->config->imagesPerPage < $imagesCount);

        $previousPageNumber = -1;
        $nextPageNumber     = -1;

        if ($pagesCount > 1) {
            $previousPageNumber = ($activePage - 1 > 0) ? $activePage - 1 : $pagesCount;
            $nextPageNumber     = ($activePage + 1 <= $pagesCount) ? $activePage + 1 : 1;
        }

        $view = new View($this->getApi(), 'album');

        $view->album              = $album;
        $view->activePage         = $activePage;
        $view->images             = $images;
        $view->imagesCount        = $imagesCount;
        $view->pagesCount         = $pagesCount;
        $view->previousPageNumber = $previousPageNumber;
        $view->nextPageNumber     = $nextPageNumber;
        $view->singleAlbumMode    = $this->isInSingleAlbumMode();

        return $view;
    }

    /**
     * @return bool
     */
    private function canUseCache()
    {
        static $ret = null;

        if ($ret !== null) {
            return $ret;
        }

        $ret = $this->cache->isWritable();

        return $ret;
    }

    /**
     * @return View
     * @throws \LogicException
     * @throws \RuntimeException
     */
    private function indexAction() // (no parameters)
    {
        if ($this->isInSingleAlbumMode()) {
            $this->doRedirect($this->url('album'));
        }

        $albums = $this->getAlbums();

        $coverImages = [];

        foreach ($albums as $album) {
            $images = $this->getImages($this->config->albumPath . '/' . $album);

            if (count($images) === 0) {
                throw new \RuntimeException(sprintf(
                    'Unable to read images from album "%s". Album may be empty or image file permissions may be too restrictive',
                    $album
                ));
            }

            $coverImages[$album] = $images[0];
        }

        $view = new View($this->getApi(), 'index');

        $view->albums      = $albums;
        $view->coverImages = $coverImages;
        $view->canUseCache = $this->canUseCache();

        return $view;
    }

    /**
     * @throws \LogicException
     * @throws \RuntimeException
     */
    private function randomAction() // album
    {
        $album = $this->getGetString('album');
        $this->assertValidAlbum($album);

        $images = $this->getImages($this->config->albumPath . '/' . $album);

        $image = $images[array_rand($images)];

        $url = $this->url('detail', ['album' => $album, 'filename' => $image->getBasename()]);

        $this->doRedirect($url);
    }

    /**
     * @param string      $mimeType
     * @param string|null $lastModified
     * @param string|null $etag
     * @param string|null $filename
     */
    private function sendImageHeaders($mimeType, $lastModified = null, $etag = null, $filename = null)
    {
        header('Content-Type: ' . $mimeType);

        header('Cache-Control: public, max-age=2592000'); // 30 days

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

    /**
     * @param string $prefix
     * @param string $albumName
     * @param string $imageName
     * @param int    $width
     * @param int    $height
     * @param int    $quality
     *
     * @return string
     */
    private function generateCacheKey($prefix, $albumName, $imageName, $width, $height, $quality)
    {
        $imageFullPath = $this->config->albumPath . '/' . $albumName . '/' . $imageName;

        $parts = [
            $prefix,
            substr(md5($albumName), 0, 10),
            substr(md5($imageName), 0, 10),
            filemtime($imageFullPath),
            $width,
            $height,
            $quality,
        ];

        return implode(',', $parts) . '.cache';
    }

    /**
     * @param string      $key
     * @param string|null $default
     *
     * @return string|null
     */
    private function getGetString($key, $default = '')
    {
        if (isset($_GET[$key]) && is_string($_GET[$key])) {
            return $_GET[$key];
        }

        return $default;
    }

    private function generateETag(FileCacheItem $cacheItem)
    {
        /* @var $fileObject \SplFileObject */
        $file = $cacheItem->get();

        return $file->getMTime();
    }

    /**
     * @throws \LogicException
     * @throws \RuntimeException
     */
    private function imageAction() // album, filename, element
    {
        $album = $this->getGetString('album');
        $this->assertValidAlbum($album);

        $basename = $this->getGetString('filename');
        $this->assertValidFilename($album, $basename);

        $prefix = $this->getGetString('element');
        if ($prefix !== 'thumb' && $prefix !== 'large') {
            throw new \RuntimeException('Invalid element.');
        }

        $width   = 0;
        $height  = 0;
        $quality = 0;

        if ($prefix === 'thumb') {
            $width   = $this->config->thumbWidth;
            $height  = $this->config->thumbHeight;
            $quality = $this->config->thumbQuality;
        } elseif ($prefix === 'large') {
            $width   = $this->config->largeWidth;
            $height  = $this->config->largeHeight;
            $quality = $this->config->largeQuality;
        }

        $cacheKey = $this->generateCacheKey($prefix, $album, $basename, $width, $height, $quality);

        if ($this->cache->hasItem($cacheKey)) {
            $cacheItem = $this->cache->getItem($cacheKey);
            $etag      = $this->generateETag($cacheItem);

            // ETag
            if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
                header('HTTP/1.1 304 Not Modified');
                return;
            }

            /* @var $file \SplFileObject */
            $file = $cacheItem->get();

            // Modification date
            if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) === $file->getMTime()) {
                header('HTTP/1.1 304 Not Modified');
                return;
            }

            $lastModified = gmdate('D, d M Y H:i:s \\G\\M\\T', $file->getMTime());
            $this->sendImageHeaders('image/jpeg', $lastModified, $etag, $prefix . '-' . $basename);
            $file->fpassthru();
            return;
        }

        $imageTools = new ImageTools();

        $localPath = $this->config->albumPath . '/' . $album . '/' . $basename;

        $dstim2 = null;

        if ($prefix === 'thumb') {
            $dstim2 = $imageTools->createThumb($imageTools->loadImage($localPath), $width, $height);
        } elseif ($prefix === 'large') {
            $dstim2 = $imageTools->scale($imageTools->loadImage($localPath), $width, $height);
        }

        if ($dstim2 === null) {
            throw new \RuntimeException('Unable to set $dstim2');
        }

        if ($this->cache->saveFromJpegImage($cacheKey, $dstim2, $quality)) {
            imagedestroy($dstim2);

            /* @var $file \SplFileObject */
            $cacheItem = $this->cache->getItem($cacheKey);
            $file      = $cacheItem->get();

            $lastModified = gmdate('D, d M Y H:i:s \\G\\M\\T', $file->getMTime());
            $this->sendImageHeaders('image/jpeg', $lastModified, $this->generateETag($cacheItem), $prefix . '-' . $basename);
            $file->fpassthru();
            return;
        }

        $lastModified = gmdate('D, d M Y H:i:s \\G\\M\\T');
        $this->sendImageHeaders('image/jpeg', $lastModified, null, $prefix . '-' . $basename);
        imagejpeg($dstim2, null, $quality);
    }
}
