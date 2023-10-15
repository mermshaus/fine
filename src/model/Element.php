<?php

declare(strict_types=1);

namespace mermshaus\fine\model;

readonly final class Element
{
    public function __construct(public string $type, public string $path, public string $name, public int $filesize = 0)
    {
    }
}
