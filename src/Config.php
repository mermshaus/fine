<?php

declare(strict_types=1);

namespace mermshaus\fine;

final class Config
{
    public string $albumPath;
    public string $cacheDir;

    public int $thumbWidth = 240;
    public int $thumbHeight = 240;
    public int $thumbQuality = 75;

    public int $largeWidth = 1920;
    public int $largeHeight = 1920;
    public int $largeQuality = 75;

    public int $imagesPerPage = 120;

    public function __construct()
    {
        $this->albumPath = getcwd();

        if (is_dir($this->albumPath . '/albums')) {
            $this->albumPath .= '/albums';
        }

        $this->cacheDir = $this->albumPath . '/.fine';
    }
}
