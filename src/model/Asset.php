<?php

declare(strict_types=1);

namespace mermshaus\fine\model;

readonly final class Asset
{
    public function __construct(public string $key, public string $type, public string $content)
    {
    }
}
