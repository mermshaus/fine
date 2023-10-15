<?php

declare(strict_types=1);

namespace mermshaus\fine\model;

readonly final class FileCacheItem
{
    public function __construct(public string $key, public mixed $value)
    {
    }
}
