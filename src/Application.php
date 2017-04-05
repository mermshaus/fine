<?php

namespace mermshaus\fine;

use Exception;
use LogicException;
use mermshaus\fine\model\Image;
use SplFileObject;

/**
 *
 */
final class Application
{
    /**
     * Version number of the application (uses Semantic Versioning)
     */
    const VERSION = '0.4.0-dev';

    /**
     *
     * @var Config
     */
    private $config;

    /**
     *
     * @var ViewScriptManager
     */
    private $viewScriptManager;

    /**
     *
     * @var FileCache
     */
    private $cache;

    /**
     *
     * @param Config $config
     * @param ViewScriptManager $viewScriptManager
     * @param FileCache $cache
     */
    public function __construct(
        Config $config,
        ViewScriptManager $viewScriptManager,
        FileCache $cache
    ) {
        $this->config = $config;

        $this->viewScriptManager = $viewScriptManager;

        $this->cache = $cache;

        set_exception_handler(function (Exception $e) {
            header('HTTP/1.1 404 Not Found');
            header('Content-Type: text/plain; charset=UTF-8');
            echo $e->getMessage() . "\n";
        });
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
    public function doRedirect($url)
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     *
     * @return bool
     */
    public function isInSingleAlbumMode()
    {
        return $this->config->singleAlbumMode;
    }

    /**
     *
     * @param string $action
     * @param array $params
     * @return string
     */
    public function url($action, array $params = array())
    {
        if (in_array($action, array('album', 'detail', 'image')) && isset($params['album']) && $params['album'] === '') {
            unset($params['album']);
        }

        $url = './';

        if (basename(__FILE__) !== 'index.php') {
            $url = basename(__FILE__);
        }

        if ($action !== 'index') {
            $params = array_merge(array('action' => $action), $params);
        }

        if (count($params) > 0) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    private function loadImage($file)
    {
        if (!is_readable($file)) {
            throw new Exception(sprintf('File %s is not readable.', basename($file)));
        }

        return new Image($file);
    }

    /**
     *
     * @param string $path
     * @return Image[]
     * @throws Exception
     */
    private function getImages($path)
    {
        $files = glob($path . '/*.{JPG,jpg,JPEG,jpeg,PNG,png,GIF,gif}', GLOB_BRACE);

        $images = array();

        foreach ($files as $file) {
            $images[] = $this->loadImage($file);
        }

        // Sorts by creation date (desc) and filename (asc)
        usort($images, function (Image $a, Image $b) {
            /*if ($a->sortDate < $b->sortDate) {
                return 1;
            } elseif ($a->sortDate > $b->sortDate) {
                return -1;
            }*/

            if ($a->getBasename() < $b->getBasename()) {
                return -1;
            } elseif ($a->getBasename() > $b->getBasename()) {
                return 1;
            }

            throw new LogicException();
        });

        return $images;
    }

    /**
     *
     * @return array
     */
    private function getAlbums()
    {
        static $albums = null;

        if ($albums !== null) {
            return $albums;
        }

        $albums = array();

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
     *
     * @throws Exception
     */
    public function run()
    {
        $action = $this->getGetString('action', 'index');

        $methodName = $action . 'Action';

        if (!method_exists($this, $methodName)) {
            throw new Exception(sprintf('Unknown action: "%s"', $action));
        }

        $ret = $this->$methodName();

        if ($ret instanceof View) {
            header('Content-Type: text/html; charset=utf-8');

            $layout = new View($this, 'layout');

            $layout->contentView = $ret;
            $layout->html_id     = 'page-' . $action;
            $layout->appVersion  = $this->getVersion();

            $layout->output();
        }
    }

    /**
     *
     */
    public function statusAction() // (no parameters)
    {
        $prefixes = array('thumb', 'large');

        $ret = '';

        $ret .= 'Cache is writable: ' . (($this->canUseCache()) ? 'yes' : '**NO**') . "\n\n";
        $ret .= 'Cache prefixes: ' . implode(', ', $prefixes) . "\n\n";

        // Clear unmanaged items

        $clearedItemsCount = $this->cache->clearUnmanagedItems($prefixes);

        $ret .= 'Deleted unaccounted files: ' . $clearedItemsCount . "\n\n";

        // Clear orphaned items

        $albums = ($this->isInSingleAlbumMode()) ? array('') : $this->getAlbums();

        foreach ($prefixes as $prefix) {
            $keysHashMap = array();

            $counterImages = 0;

            foreach ($albums as $album) {
                $images = $this->getImages($this->config->albumPath . '/' . $album);

                foreach ($images as $image) {
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
                    } else {
                        throw new Exception(sprintf('Unknown prefix "%s" in status action', $prefix));
                    }

                    $cacheKey = $this->generateCacheId(
                        $prefix,
                        $album . '/' . $image->getBasename(),
                        $this->config->albumPath . '/' . $album . '/' . $image->getBasename(),
                        $width,
                        $height,
                        $quality
                    );

                    if ($this->cache->hasItem($cacheKey)) {
                        $keysHashMap[$cacheKey] = true;
                    }

                    $counterImages++;
                }
            }

            $clearedOrphanedItemsCount = $this->cache->clearItemsNotInList($prefix, $keysHashMap);

            $ret .= $prefix . "\n";
            $ret .= "Images found: " . $counterImages . "\n";
            $ret .= "Active elements in cache: " . count($keysHashMap) . "\n";
            $ret .= "Orphaned elements deleted from cache: " . $clearedOrphanedItemsCount . "\n\n";
        }

        $view = new View($this, 'status');

        $view->output = $ret;

        return $view;
    }

    /**
     *
     * @staticvar array $jsonStore
     * @param string $resourceKey
     * @return array
     * @throws Exception
     */
    public function loadResource($resourceKey)
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
            throw new Exception(sprintf('Unknown resource file "%s"', $resourceKey));
        }

        return $jsonStore[$resourceKey];
    }

    /**
     *
     */
    private function assetAction() // file
    {
        $file = $this->getGetString('file', '');

        // ETag
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === md5(filemtime(__FILE__) . '-' . $file)) {
            header('HTTP/1.1 304 Not Modified');
            return;
        }

        // Modification date
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) === filemtime(__FILE__)) {
            header('HTTP/1.1 304 Not Modified');
            return;
        }

        $data = $this->loadResource($file);

        header('Content-Type: ' . $data['type'] . '; charset=UTF-8');
        header('ETag: ' . md5(filemtime(__FILE__) . '-' . $file));

        header('Cache-Control: public, max-age=2592000'); // 30 days

        $lastModified = gmdate('D, d M Y H:i:s \\G\\M\\T', filemtime(__FILE__));

        header('Last-Modified: ' . $lastModified);

        echo $data['content'];
    }

    /**
     * @param $album
     * @throws Exception
     */
    private function assertValidAlbum($album)
    {
        if (!is_string($album)) {
            throw new LogicException();
        }

        if ($this->isInSingleAlbumMode() && $album === '') {
            return;
        }

        if (!in_array($album, $this->getAlbums(), true)) {
            throw new Exception(sprintf('Unknown album "%s"', $album));
        }
    }

    /**
     * @param string $album
     * @param string $filename
     * @throws Exception
     */
    private function assertValidFilename($album, $filename)
    {
        if (!is_string($album)) {
            throw new LogicException();
        }

        if (!is_string($filename)) {
            throw new LogicException();
        }

        $this->assertValidAlbum($album);

        $normalizedAlbumPath = realpath($this->config->albumPath . '/' . $album);

        if ($normalizedAlbumPath === false) {
            throw new LogicException('Album path is empty.');
        }

        $normalizedFilePath = realpath($normalizedAlbumPath . '/' . $filename);

        if ($normalizedFilePath === false) {
            throw new Exception('File does not exist.');
        }

        if ($normalizedFilePath !== $normalizedAlbumPath . '/' . $filename) {
            throw new Exception('Filename is not normalized.');
        }

        if ($normalizedAlbumPath !== pathinfo($normalizedFilePath, PATHINFO_DIRNAME)) {
            throw new LogicException('This should never happen.');
        }
    }

    /**
     *
     */
    private function detailAction() // album, filename
    {
        $album = $this->getGetString('album');
        $this->assertValidAlbum($album);

        $filename = $this->getGetString('filename');
        $this->assertValidFilename($album, $filename);

        $imageUrl = $this->url('image', array('album' => $album, 'filename' => $filename, 'element' => 'large'));

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
                $prevImageUrl = $this->url('detail', array('album' => $album, 'filename' => $images[$i - 1]->getBasename()));
            } else {
                $prevImageUrl = $this->url('detail', array('album' => $album, 'filename' => $images[count($images) - 1]->getBasename()));
            }

            if ($i + 1 < count($images)) {
                $nextImageUrl = $this->url('detail', array('album' => $album, 'filename' => $images[$i + 1]->getBasename()));
            } else {
                $nextImageUrl = $this->url('detail', array('album' => $album, 'filename' => $images[0]->getBasename()));
            }
        }

        $view = new View($this, 'detail');

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
     *
     * @throws Exception
     */
    private function albumAction() // album, page
    {
        $config = $this->config;

        $album = $this->getGetString('album');
        $this->assertValidAlbum($album);

        $activePage = $this->getGetString('page', '1');
        if (preg_match('/\A[1-9][0-9]*\z/', $activePage) === 0) {
            throw new Exception('Value for page parameter must be >= 1.');
        }
        $activePage = (int) $activePage;

        $images = $this->getImages($this->config->albumPath . '/' . $album);

        $imagesCount = count($images);

        $images = array_slice($images, ($activePage - 1) * $config->imagesPerPage, $config->imagesPerPage);

        if (count($images) === 0) {
            throw new Exception('No data for given parameters.');
        }

        $pagesCount = 0;

        do {
            $pagesCount++;
        } while ($pagesCount * $config->imagesPerPage < $imagesCount);

        $previousPageNumber = -1;
        $nextPageNumber     = -1;

        if ($pagesCount > 1) {
            $previousPageNumber = ($activePage - 1 > 0)            ? $activePage - 1 : $pagesCount;
            $nextPageNumber     = ($activePage + 1 <= $pagesCount) ? $activePage + 1 : 1;
        }

        $view = new View($this, 'album');

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
     *
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
     *
     */
    private function indexAction() // (no parameters)
    {
        if ($this->isInSingleAlbumMode()) {
            $this->doRedirect($this->url('album'));
        }

        $albums = $this->getAlbums();

        $coverImages = array();

        foreach ($albums as $album) {
            $images = $this->getImages($this->config->albumPath . '/' . $album);

            $coverImages[$album] = $images[0];
        }

        $view = new View($this, 'index');

        $view->albums      = $albums;
        $view->coverImages = $coverImages;
        $view->canUseCache = $this->canUseCache();

        return $view;
    }

    /**
     * @throws Exception
     */
    private function randomAction() // album
    {
        $album = $this->getGetString('album');
        $this->assertValidAlbum($album);

        $images = $this->getImages($this->config->albumPath . '/' . $album);

        $image = $images[array_rand($images)];

        $url = $this->url('detail', array('album' => $album, 'filename' => $image->getBasename()));

        $this->doRedirect($url);
    }

    /**
     *
     * @param string $file
     * @param string $etag
     * @param string $filename
     */
    private function send($mimeType, $lastModified = '', $etag = '', $filename = '')
    {
        header('Content-Type: ' . $mimeType);

        if ($lastModified !== '') {
            header('Last-Modified: ' . $lastModified);
        }

        if ($filename !== '') {
            header('Content-Disposition: inline; filename="' . $filename . '"');
        }

        header('Cache-Control: public, max-age=2592000'); // 30 days

        if ($etag !== '') {
            header('ETag: ' . $etag);
        }
    }

    private function generateCacheId($prefix, $path, $localPath, $width, $height, $quality)
    {
        $parts = array(
            $prefix,
            md5($path),
            filemtime($localPath),
            $width,
            $height,
            $quality
        );

        $cacheId = implode(',', $parts) . '.cache';

        return $cacheId;
    }

    /**
     *
     * @param string $key
     * @param string|null $default
     * @return string|null
     */
    private function getGetString($key, $default = '')
    {
        if (isset($_GET[$key]) && is_string($_GET[$key])) {
            return $_GET[$key];
        }

        return $default;
    }

    /**
     *
     * @throws Exception
     */
    private function imageAction() // album, filename, element
    {
        $album = $this->getGetString('album');
        $this->assertValidAlbum($album);

        $filename = $this->getGetString('filename');
        $this->assertValidFilename($album, $filename);

        $element = $this->getGetString('element');
        if ($element !== 'thumb' && $element !== 'large') {
            throw new Exception('Invalid element.');
        }

        if ($element === 'thumb') {
            $width   = $this->config->thumbWidth;
            $height  = $this->config->thumbHeight;
            $quality = $this->config->thumbQuality;
        } elseif ($element === 'large') {
            $width   = $this->config->largeWidth;
            $height  = $this->config->largeHeight;
            $quality = $this->config->largeQuality;
        }

        $localPath = $this->config->albumPath . '/' . $album . '/' . $filename;
        $cacheKey  = $this->generateCacheId($element, $album . '/' . $filename, $localPath, $width, $height, $quality);

        if ($this->cache->hasItem($cacheKey)) {
            // ETag
            if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $cacheKey) {
                header('HTTP/1.1 304 Not Modified');
                return;
            }

            /* @var $fileObject SplFileObject */
            $fileObject = $this->cache->getItem($cacheKey)->get();

            // Modification date
            if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) === $fileObject->getMTime()) {
                header('HTTP/1.1 304 Not Modified');
                return;
            }

            $lastModified = gmdate('D, d M Y H:i:s \\G\\M\\T', $fileObject->getMTime());
            $this->send('image/jpeg', $lastModified, $cacheKey, $element . '-' . $filename);
            $fileObject->fpassthru();
            return;
        }

        $imageTools = new ImageTools();

        if ($element === 'thumb') {
            $dstim2 = $imageTools->createThumb($imageTools->loadImage($localPath), $width, $height);
        } elseif ($element === 'large') {
            $dstim2 = $imageTools->scale($imageTools->loadImage($localPath), $width, $height);
        }

        if ($this->cache->saveFromJpegImage($cacheKey, $dstim2, $quality)) {
            imagedestroy($dstim2);

            /* @var $fileObject SplFileObject */
            $fileObject = $this->cache->getItem($cacheKey)->get();

            $lastModified = gmdate('D, d M Y H:i:s \\G\\M\\T', $fileObject->getMTime());
            $this->send('image/jpeg', $lastModified, $cacheKey, $element . '-' . $filename);
            $fileObject->fpassthru();
            return;
        }

        $lastModified = gmdate('D, d M Y H:i:s \\G\\M\\T');
        $this->send('image/jpeg', $lastModified, $cacheKey, $element . '-' . $filename);
        imagejpeg($dstim2, null, $quality);
    }
}
