<?php

declare(strict_types=1);

namespace mermshaus\fine;

use Exception;
use LogicException;
use mermshaus\fine\model\AbstractViewModel;
use mermshaus\fine\model\ViewModelAlbum;
use mermshaus\fine\model\ViewModelIndex;
use mermshaus\fine\model\ViewModelLayout;
use mermshaus\fine\model\ViewModelStatus;
use RuntimeException;
use Throwable;

final class Application
{
    /**
     * Version number of the application (uses Semantic Versioning).
     */
    public const VERSION = '1.0.0-dev';

    private Config $config;
    private ViewScriptManager $viewScriptManager;
    private FileCache $cache;
    private ?ApplicationApi $api = null;

    public function __construct(Config $config, ViewScriptManager $viewScriptManager, FileCache $cache)
    {
        $this->config = $config;
        $this->viewScriptManager = $viewScriptManager;
        $this->cache = $cache;

        // We're not using a type hint here in order to support both PHP 5/7 Exceptions and PHP 7 Throwables
        set_exception_handler(function ($e) {
            /** @var Exception|Throwable $e */
            header('HTTP/1.1 404 Not Found');
            header('Content-Type: text/plain; charset=UTF-8');
            echo $e->getMessage() . "\n";
        });
    }

    private function getApi(): ApplicationApi
    {
        if ($this->api === null) {
            $this->api = new ApplicationApi($this);
        }

        return $this->api;
    }

    public function getViewScriptManager(): ViewScriptManager
    {
        return $this->viewScriptManager;
    }

    public function getVersion(): string
    {
        return self::VERSION;
    }

    private function doRedirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    public function url(string $action, array $params = []): string
    {
        if (
            isset($params['album'])
            && $params['album'] === ''
            && in_array($action, ['album', 'detail', 'image'], true)
        ) {
            unset($params['album']);
        }

        $url = './';

        if (basename(__FILE__) !== 'index.php') {
            $url = basename(__FILE__);
        }

        if ($action !== 'index') {
            $params = array_merge(['action' => $action], $params);
        }

        if ($action === 'index' && isset($params['path']) && $params['path'] === '') {
            unset($params['path']);
        }

        if (count($params) > 0) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    /**
     * @throws RuntimeException
     */
    private function loadImage(string $file): model\Image
    {
        if (!is_readable($file)) {
            throw new RuntimeException(sprintf('File %s is not readable.', basename($file)));
        }

        return new model\Image($file);
    }

    /**
     * @return array<model\Image>
     *
     * @throws LogicException
     * @throws RuntimeException
     */
    private function getImages(string $path): array
    {
        $files = glob($path . '/*.{JPG,jpg,JPEG,jpeg,PNG,png,GIF,gif}', GLOB_BRACE);

        $images = [];

        foreach ($files as $file) {
            $images[] = $this->loadImage($file);
        }

        $callback = function (model\Image $a, model\Image $b): int {
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

            throw new LogicException('Failed sorting images');
        };

        // Sorts by creation date (desc) and filename (asc)
        usort($images, $callback);

        return $images;
    }

    /**
     * @return array<string>
     *
     * @throws LogicException
     * @throws RuntimeException
     */
    private function getAlbums(): array
    {
        static $albums = null;

        if ($albums !== null) {
            return $albums;
        }

        $albums = [];

        foreach (glob($this->config->albumPath . '/*', GLOB_ONLYDIR) as $p) {
            $tmp = basename($p);

            if ($tmp !== '.fine') {
                $images = $this->getImages($this->config->albumPath . '/' . $tmp);

                if (count($images) === 0) {
                    continue;
                }

                $albums[] = $tmp;
            }
        }

        usort($albums, function ($a, $b) {
            return strcasecmp($a, $b);
        });

        return $albums;
    }

    private function getElements(string $path): array
    {
        $globPath = $this->combinePath([$this->config->albumPath, $path]) . '/*';

        $elements = [];

        foreach (glob($globPath) as $p) {
            $basename = basename($p);

            if ($basename === '.fine') {
                continue;
            }

            if (is_dir($p)) {
                $elements[] = [
                    'type' => 'directory',
                    'path' => substr($p, strlen($this->config->albumPath . '/')),
                    'name' => $basename,
                ];
            } elseif (is_file($p)) {
                $elements[] = [
                    'type' => 'image',
                    'path' => substr($p, strlen($this->config->albumPath . '/')),
                    'name' => $basename,
                ];
            }
        }

        usort($elements, function ($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        return $elements;
    }

    /**
     * @throws RuntimeException
     */
    public function run(): void
    {
        $action = $this->getGetString('action', 'index');

        $methodName = $action . 'Action';

        if (!method_exists($this, $methodName)) {
            throw new RuntimeException(sprintf('Unknown action: "%s"', $action));
        }

        $ret = $this->{$methodName}();

        if ($ret instanceof AbstractViewModel) {
            header('Content-Type: text/html; charset=utf-8');

            $viewModelLayout = new ViewModelLayout(
                $this->getApi(),
                'layout',
                $ret,
                'page-' . $action,
                $this->getVersion(),
            );

            $viewModelLayout->output();
        }
    }

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
    private function statusAction(): ViewModelStatus
    {
        // (no parameters)
        // (no parameters)
        $prefixes = ['thumb', 'large'];

        $ret = '';

        $ret .= 'Cache is writable: ' . ($this->canUseCache() ? 'yes' : '**NO**') . "\n\n";
        $ret .= 'Cache prefixes: ' . implode(', ', $prefixes) . "\n\n";

        // Clear unmanaged items

        $clearedItemsCount = $this->cache->clearUnmanagedItems($prefixes);

        $ret .= 'Deleted unaccounted files: ' . $clearedItemsCount . "\n\n";

        // Clear orphaned items

        $info = [];

        $albums = $this->getAlbums();

        $first = true;

        foreach ($prefixes as $prefix) {
            $keysHashMap = [];

            foreach ($albums as $album) {
                if (!isset($info[$album])) {
                    $info[$album] = [
                        'count' => 0,
                        'size' => 0,
                        'cache' => [],
                    ];
                }

                if (!isset($info[$album]['cache'][$prefix])) {
                    $info[$album]['cache'][$prefix] = [
                        'count' => 0,
                        'size' => 0,
                    ];
                }

                $images = $this->getImages($this->config->albumPath . '/' . $album);

                foreach ($images as $image) {
                    if ($prefix === 'thumb') {
                        $width = $this->config->thumbWidth;
                        $height = $this->config->thumbHeight;
                        $quality = $this->config->thumbQuality;
                    } elseif ($prefix === 'large') {
                        $width = $this->config->largeWidth;
                        $height = $this->config->largeHeight;
                        $quality = $this->config->largeQuality;
                    } else {
                        throw new RuntimeException(sprintf('Unknown prefix "%s" in status action', $prefix));
                    }

                    $cacheKey = $this->generateCacheKey(
                        $prefix,
                        $album,
                        $image->getBasename(),
                        $width,
                        $height,
                        $quality,
                    );

                    if ($this->cache->hasItem($cacheKey)) {
                        $keysHashMap[$cacheKey] = true;
                        $info[$album]['cache'][$prefix]['count']++;

                        $info[$album]['cache'][$prefix]['size'] += $this->cache
                            ->getItem($cacheKey)
                            ->get()
                            ->getSize()
                        ;
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

        return new ViewModelStatus($this->getApi(), 'status', $prefixes, $ret, $info);
    }

    /**
     * @staticvar array $jsonStore
     *
     * @throws RuntimeException
     */
    private function loadResource(string $resourceKey): array
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
            throw new RuntimeException(sprintf('Unknown resource file "%s"', $resourceKey));
        }

        return $jsonStore[$resourceKey];
    }

    /**
     * @throws RuntimeException
     */
    private function assetAction(): void
    {
        // file
        $file = $this->getGetString('file');

        $mtime = filemtime(__FILE__);

        // ETag
        if (
            isset($_SERVER['HTTP_IF_NONE_MATCH'])
            && trim($_SERVER['HTTP_IF_NONE_MATCH']) === md5($mtime . '-' . $file)
        ) {
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
     * @throws LogicException
     * @throws RuntimeException
     */
    private function assertValidAlbum(string $album): void
    {
        if (!in_array($album, $this->getAlbums(), true)) {
            throw new RuntimeException(sprintf('Unknown album "%s"', $album));
        }
    }

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
    private function assertValidFilename(string $album, string $filename): void
    {
        $this->assertValidAlbum($album);

        $normalizedAlbumPath = realpath($this->config->albumPath . '/' . $album);

        if ($normalizedAlbumPath === false) {
            throw new LogicException('Album path is empty.');
        }

        $normalizedFilePath = realpath($normalizedAlbumPath . '/' . $filename);

        if ($normalizedFilePath === false) {
            throw new RuntimeException('File does not exist.');
        }

        if ($normalizedFilePath !== $normalizedAlbumPath . '/' . $filename) {
            throw new RuntimeException('Filename is not normalized.');
        }

        if ($normalizedAlbumPath !== pathinfo($normalizedFilePath, PATHINFO_DIRNAME)) {
            throw new LogicException('This should never happen.');
        }
    }

    /**
     * @param array<string> $parts
     */
    private function combinePath(array $parts): string
    {
        $nonEmptyParts = array_filter($parts, function ($part) {
            return $part !== '';
        });

        return implode('/', $nonEmptyParts);
    }

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
    private function detailAction(): model\ViewModelDetail
    {
        // path
        $path = $this->getGetString('path');

        $absPath = $this->config->albumPath . '/' . $path;
        $dirname = dirname($absPath);
        $pathDirname = dirname($path);
        $basename = basename($absPath);

        if ($pathDirname === '.') {
            $pathDirname = '';
        }

        if (pathinfo($absPath, PATHINFO_EXTENSION) === 'gif') {
            $imageUrl = $this->url('gif', ['path' => $path]);
        } else {
            $imageUrl = $this->url('image', ['path' => $path, 'element' => 'large']);
        }

        $images = $this->getImages($dirname);

        $i = 0;
        foreach ($images as $image) {
            if ($image->getBasename() === $basename) {
                break;
            }
            $i++;
        }

        $image = null;

        $page = 1;

        while ($i >= $page * $this->config->imagesPerPage) {
            $page++;
        }

        $prevImageUrl = '';
        $nextImageUrl = '';

        if (count($images) > 1) {
            if ($i - 1 >= 0) {
                $prevImageUrl = $this->url('detail', [
                    'path' => $this->combinePath([$pathDirname, $images[$i - 1]->getBasename()]),
                ]);
            } else {
                $prevImageUrl = $this->url('detail', [
                    'path' => $this->combinePath([$pathDirname, $images[count($images) - 1]->getBasename()]),
                ]);
            }

            if ($i + 1 < count($images)) {
                $nextImageUrl = $this->url('detail', [
                    'path' => $this->combinePath([$pathDirname, $images[$i + 1]->getBasename()]),
                ]);
            } else {
                $nextImageUrl = $this->url('detail', [
                    'path' => $this->combinePath([$pathDirname, $images[0]->getBasename()]),
                ]);
            }
        }

        return new model\ViewModelDetail(
            $this->getApi(),
            'detail',
            $pathDirname,
            $i,
            $imageUrl,
            $images,
            $image,
            $prevImageUrl,
            $nextImageUrl,
            $page,
            $path,
        );
    }

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
    private function albumAction(): ViewModelAlbum
    {
        // album, page
        // album, page
        $album = $this->getGetString('album');
        $this->assertValidAlbum($album);

        $activePage = $this->getGetString('page', '1');
        /** @noinspection NotOptimalRegularExpressionsInspection */
        if (preg_match('/\\A[1-9][0-9]*\\z/', $activePage) === 0) {
            throw new RuntimeException('Value for page parameter must be >= 1.');
        }
        $activePage = (int) $activePage;

        $images = $this->getImages($this->config->albumPath . '/' . $album);

        $imagesCount = count($images);

        $images = array_slice($images, ($activePage - 1) * $this->config->imagesPerPage, $this->config->imagesPerPage);

        if (count($images) === 0) {
            throw new RuntimeException('No data for given parameters.');
        }

        $pagesCount = 0;

        do {
            $pagesCount++;
        } while ($pagesCount * $this->config->imagesPerPage < $imagesCount);

        $previousPageNumber = -1;
        $nextPageNumber = -1;

        if ($pagesCount > 1) {
            $previousPageNumber = $activePage - 1 > 0 ? $activePage - 1 : $pagesCount;
            $nextPageNumber = $activePage + 1 <= $pagesCount ? $activePage + 1 : 1;
        }

        return new ViewModelAlbum(
            $this->getApi(),
            'album',
            $album,
            $activePage,
            $images,
            $imagesCount,
            $pagesCount,
            $previousPageNumber,
            $nextPageNumber,
        );
    }

    private function canUseCache(): bool
    {
        static $ret = null;

        if ($ret !== null) {
            return $ret;
        }

        $ret = $this->cache->isWritable();

        return $ret;
    }

    private function indexAction(): AbstractViewModel
    {
        // path
        $path = $this->getGetString('path');

        $elements = $this->getElements($path);

        $navigation = [];

        $pathParts = $path !== '' ? explode('/', $path) : [];

        if (count($pathParts) > 0) {
            $navigation[] = [
                'title' => 'Home',
                'url' => $this->url('index', ['path' => '']),
            ];

            $curPath = '';

            foreach ($pathParts as $pathPart) {
                $curPath .= $curPath === '' ? $pathPart : '/' . $pathPart;

                $navigation[] = [
                    'title' => $pathPart,
                    'url' => $this->url('index', ['path' => $curPath]),
                ];
            }
        }

        return new ViewModelIndex(
            $this->getApi(),
            'index',
            $path,
            $navigation,
            $elements,
            $this->canUseCache(),
            basename($path),
        );
    }

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
    private function randomAction(): void
    {
        // album
        $album = $this->getGetString('album');
        $this->assertValidAlbum($album);

        $images = $this->getImages($this->config->albumPath . '/' . $album);

        $image = $images[array_rand($images)];

        $url = $this->url('detail', ['album' => $album, 'filename' => $image->getBasename()]);

        $this->doRedirect($url);
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

    private function generateCacheKey(
        string $prefix,
        string $albumName,
        string $imageName,
        int $width,
        int $height,
        int $quality
    ): string {
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

    private function getGetString(string $key, string $default = ''): string
    {
        if (isset($_GET[$key]) && is_string($_GET[$key])) {
            return $_GET[$key];
        }

        return $default;
    }

    private function generateETag(FileCacheItem $cacheItem): string
    {
        /* @var $file \SplFileObject */
        $file = $cacheItem->get();

        $mtime = $file->getMTime();

        if ($mtime === false) {
            return '0';
        }

        return (string) $mtime;
    }

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
    private function imageAction(): void
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

                return;
            }

            /* @var $file \SplFileObject */
            $file = $cacheItem->get();

            // Modification date
            if (
                isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
                && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) === $file->getMTime()
            ) {
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

        if ($this->cache->saveFromJpegImage($cacheKey, $dstim2, $quality)) {
            imagedestroy($dstim2);

            /* @var $file \SplFileObject */
            $cacheItem = $this->cache->getItem($cacheKey);
            $file = $cacheItem->get();

            $lastModified = gmdate('D, d M Y H:i:s \\G\\M\\T', $file->getMTime());
            $this->sendImageHeaders(
                'image/jpeg',
                $lastModified,
                $this->generateETag($cacheItem),
                $prefix . '-' . $basename,
            );
            $file->fpassthru();

            return;
        }

        $lastModified = gmdate('D, d M Y H:i:s \\G\\M\\T');
        $this->sendImageHeaders('image/jpeg', $lastModified, null, $prefix . '-' . $basename);
        imagejpeg($dstim2, null, $quality);
    }

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
    private function gifAction(): void
    {
        // album, filename
        $album = $this->getGetString('album');
        $this->assertValidAlbum($album);

        $basename = $this->getGetString('filename');
        $this->assertValidFilename($album, $basename);

        // $lastModified = gmdate('D, d M Y H:i:s \\G\\M\\T');
        // $this->sendImageHeaders('image/jpeg', $lastModified, null, $prefix . '-' . $basename);
        readfile(__DIR__ . '/albums/' . $album . '/' . $basename);
    }
}
