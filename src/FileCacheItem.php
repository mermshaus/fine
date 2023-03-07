<?php

declare(strict_types=1);

namespace mermshaus\fine;

final class FileCacheItem
{
    private string $key;

    private mixed $value;

    public function __construct(string $key, mixed $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function get(): mixed
    {
        return $this->value;
    }
}
