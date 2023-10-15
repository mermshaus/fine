<?php

declare(strict_types=1);

namespace mermshaus\fine\Action;

use InvalidArgumentException;
use LogicException;
use mermshaus\fine\ApplicationApi;
use mermshaus\fine\Config;
use mermshaus\fine\model\AbstractViewModel;
use mermshaus\fine\model\Element;
use mermshaus\fine\model\Image;
use RuntimeException;

abstract class AbstractAction
{
    public function __construct(protected readonly ApplicationApi $api, protected readonly Config $config)
    {
    }

    abstract public function execute(): ?AbstractViewModel;

    /**
     * @param array<string> $parts
     */
    protected function combinePath(array $parts): string
    {
        $nonEmptyParts = array_filter($parts, function ($part) {
            return $part !== '';
        });

        return implode('/', $nonEmptyParts);
    }

    protected function getGetString(string $key, string $default = ''): string
    {
        if (isset($_GET[$key]) && is_string($_GET[$key])) {
            return $_GET[$key];
        }

        return $default;
    }

    /**
     * @return array<Image>
     *
     * @throws LogicException
     * @throws RuntimeException
     */
    protected function getImages(string $path): array
    {
        $files = glob($path . '/*.{JPG,jpg,JPEG,jpeg,PNG,png,GIF,gif}', GLOB_BRACE);

        $images = [];

        foreach ($files as $file) {
            $images[] = $this->loadImage($file);
        }

        $callback = function (Image $a, Image $b): int {
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
     * @throws RuntimeException
     */
    private function loadImage(string $file): Image
    {
        if (!is_readable($file)) {
            throw new RuntimeException(sprintf('File %s is not readable.', basename($file)));
        }

        return new Image($file);
    }

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
    protected function assertValidAlbum(string $album): void
    {
        if (!in_array($album, $this->getAlbums(), true)) {
            throw new RuntimeException(sprintf('Unknown album "%s"', $album));
        }
    }

    /**
     * @return list<string>
     *
     * @throws LogicException
     * @throws RuntimeException
     */
    protected function getAlbums(string $localPath = '/'): array
    {
        if (!str_starts_with($localPath, '/')) {
            throw new InvalidArgumentException(
                sprintf('Parameter localPath must start with a slash. %s given.', $localPath),
            );
        }

        $separator = str_ends_with($localPath, '/') ? '' : '/';

        $pattern = $this->config->albumPath . $localPath . $separator . '*';

        $albums = [];

        foreach (glob($pattern, GLOB_ONLYDIR) as $p) {
            $tmp = basename($p);

            if ($tmp === '.fine') {
                continue;
            }

            $albums[] = $tmp;
        }

        usort($albums, function ($a, $b) {
            return strcasecmp($a, $b);
        });

        return $albums;
    }

    /**
     * @return array<Element>
     */
    protected function getElements(string $path): array
    {
        if (!str_starts_with($path, '/')) {
            throw new InvalidArgumentException(sprintf('Parameter path has to start with "/". "%s" given.', $path));
        }

        $ignoreList = ['.fine'];

        $trailingSeparator = str_ends_with($path, '/') ? '' : '/';

        $globPattern = $this->config->albumPath . $path . $trailingSeparator . '*';

        $elements = [];

        foreach (glob($globPattern) as $p) {
            $basename = basename($p);

            if (in_array($basename, $ignoreList, true)) {
                continue;
            }

            if (is_dir($p)) {
                $elements[] = new Element(
                    type: 'directory',
                    path: substr($p, strlen($this->config->albumPath )),
                    name: $basename,
                );
            } elseif (is_file($p)) {
                $elements[] = new Element(
                    type: 'file',
                    path: substr($p, strlen($this->config->albumPath )),
                    name: $basename,
                    filesize: filesize($p),
                );
            }
        }

        usort($elements, function (Element $a, Element $b) {
            return strcasecmp($a->name, $b->name);
        });

        return $elements;
    }

    /**
     * @throws LogicException
     * @throws RuntimeException
     */
    protected function assertValidFilename(string $album, string $filename): void
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

    protected function generateCacheKey(
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
}
