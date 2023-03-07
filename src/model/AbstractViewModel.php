<?php

declare(strict_types=1);

namespace mermshaus\fine\model;

use mermshaus\fine\ApplicationApi;
use RuntimeException;

abstract class AbstractViewModel
{
    private ApplicationApi $api;

    private string $script;

    public function __construct(ApplicationApi $api, string $script)
    {
        $this->api = $api;
        $this->script = $script;
    }

    public function e(mixed $s): string
    {
        return $this->api->e($s);
    }

    public function url(string $action, array $params = []): string
    {
        return $this->api->url($action, $params);
    }

    /**
     * @throws RuntimeException
     */
    private function doInclude(string $resourceKey): void
    {
        $this->api->doInclude($resourceKey, $this);
    }

    /**
     * @throws RuntimeException
     */
    public function output(): void
    {
        $this->doInclude($this->script);
    }
}
