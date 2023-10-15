<?php

declare(strict_types=1);

namespace mermshaus\fine\model;

final class DirectoryInfo
{
    public readonly string $basename;

    public function __construct(
        public readonly string $path = '/',
        public int $directoryCount = 0,
        public int $fileCount = 0,
        public int $size = 0,
    ) {
        $this->basename = basename($this->path);
    }
}
