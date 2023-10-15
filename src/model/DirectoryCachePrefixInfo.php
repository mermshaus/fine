<?php

declare(strict_types=1);

namespace mermshaus\fine\model;

final class DirectoryCachePrefixInfo
{
    public function __construct(public readonly string $prefix, public int $fileCount = 0, public int $size = 0)
    {
    }
}
