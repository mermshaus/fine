<?php

declare(strict_types=1);

namespace mermshaus\fine\Action;

use mermshaus\fine\ApplicationApi;
use mermshaus\fine\Config;
use mermshaus\fine\FileCache;
use mermshaus\fine\model\AbstractViewModel;
use mermshaus\fine\model\DirectoryInfo;
use mermshaus\fine\model\ViewModelStatus;
use RuntimeException;

final class StatusAction extends AbstractAction
{
    public function __construct(ApplicationApi $api, Config $config, private readonly FileCache $cache)
    {
        parent::__construct($api, $config);
    }

    public function execute(): ?AbstractViewModel
    {
        $prefixes = ['thumb', 'large'];

        $ret = '';

        $ret .= 'Cache is writable: ' . ($this->cache->isWritable() ? 'yes' : '**NO**') . "\n\n";
        $ret .= 'Cache prefixes: ' . implode(', ', $prefixes) . "\n\n";

        // Clear unmanaged items

        #$clearedItemsCount = $this->cache->clearUnmanagedItems($prefixes);

        #$ret .= 'Deleted unaccounted files: ' . $clearedItemsCount . "\n\n";


        $startPath = '/';

        $data=[];

        $f = function ($path) use (&$data, &$f) {
            $di = new DirectoryInfo($path);

            foreach ($this->getElements($path) as $element) {
                if ($element->type === 'directory') {
                    $f($element->path);
                    $di->directoryCount++;
                } elseif ($element->type === 'file') {
                    $di->fileCount++;
                    $di->size+=$element->filesize;
                }
            }

            $data[] = $di;
        };

        $f($startPath);
echo '<pre>';
        var_dump($data);
        echo '</pre>';


        // Clear orphaned items




//        $info = [];
//
//        $albums = $this->getAlbums('/');
//
//        $first = true;
//
//        foreach ($prefixes as $prefix) {
//            $keysHashMap = [];
//
//            foreach ($albums as $album) {
//                if (!isset($info[$album])) {
//                    $info[$album] = [
//                        'count' => 0,
//                        'size' => 0,
//                        'cache' => [],
//                    ];
//                }
//
//                if (!isset($info[$album]['cache'][$prefix])) {
//                    $info[$album]['cache'][$prefix] = [
//                        'count' => 0,
//                        'size' => 0,
//                    ];
//                }
//
//                $images = $this->getImages($this->config->albumPath . '/' . $album);
//
//                foreach ($images as $image) {
//                    if ($prefix === 'thumb') {
//                        $width = $this->config->thumbWidth;
//                        $height = $this->config->thumbHeight;
//                        $quality = $this->config->thumbQuality;
//                    } elseif ($prefix === 'large') {
//                        $width = $this->config->largeWidth;
//                        $height = $this->config->largeHeight;
//                        $quality = $this->config->largeQuality;
//                    } else {
//                        throw new RuntimeException(sprintf('Unknown prefix "%s" in status action', $prefix));
//                    }
//
//                    $cacheKey = $this->generateCacheKey(
//                        $prefix,
//                        $album,
//                        $image->getBasename(),
//                        $width,
//                        $height,
//                        $quality,
//                    );
//
//                    if ($this->cache->hasItem($cacheKey)) {
//                        $keysHashMap[$cacheKey] = true;
//                        $info[$album]['cache'][$prefix]['count']++;
//
//                        $info[$album]['cache'][$prefix]['size'] += $this->cache
//                            ->getItem($cacheKey)
//                            ->value
//                            ->getSize()
//                        ;
//                    }
//
//                    if ($first) {
//                        $info[$album]['count']++;
//                        $info[$album]['size'] += $image->getFileSize();
//                    }
//                }
//            }
//
//            #$clearedOrphanedItemsCount = $this->cache->clearItemsNotInList($prefix, $keysHashMap);
//
//            #$ret .= $prefix . ': orphaned elements deleted from cache: ' . $clearedOrphanedItemsCount . "\n";
//
//            $first = false;
//        }

        return new ViewModelStatus($this->api, 'status', $prefixes, $ret, $data);
    }
}
