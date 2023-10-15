<?php

declare(strict_types=1);

namespace mermshaus\fine\model;

readonly final class NavigationItem
{
    public function __construct(public string $title, public string $url)
    {
    }
}
