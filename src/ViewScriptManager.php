<?php

declare(strict_types=1);

namespace mermshaus\fine;

use Closure;
use RuntimeException;

final class ViewScriptManager
{
    private array $scripts = [];

    public function addScript(string $key, Closure $content): void
    {
        $this->scripts[$key] = $content;
    }

    /**
     * @throws RuntimeException
     */
    public function getScript(string $key): Closure
    {
        if (!isset($this->scripts[$key])) {
            throw new RuntimeException(sprintf('Script not found: "%s"', $key));
        }

        return $this->scripts[$key];
    }
}
