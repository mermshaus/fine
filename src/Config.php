<?php

namespace mermshaus\fine;

/**
 *
 */
final class Config
{
    public $albumPath;
    public $cacheDir;

    public $singleAlbumMode;

    public $thumbWidth   = 240;
    public $thumbHeight  = 240;
    public $thumbQuality = 75;

    public $largeWidth   = 1920;
    public $largeHeight  = 1920;
    public $largeQuality = 75;

    /**
     *
     * @var int
     */
    public $imagesPerPage = 120;

    /**
     *
     */
    public function __construct($configPath = null)
    {
        // Default values

        $this->albumPath       = __DIR__;
        $this->singleAlbumMode = true;

        if (is_dir(__DIR__ . '/albums')) {
            $this->albumPath       = __DIR__ . '/albums';
            $this->singleAlbumMode = false;
        }

        $this->cacheDir = $this->albumPath . '/.fine';

        // Override with config file

        if ($configPath !== null) {
            // todo
        }
    }
}
