<?php

declare(strict_types=1);

namespace mermshaus\fine;

use RuntimeException;

final class ApplicationApi
{
    private Application $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    public function e(mixed $s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }

    public function url(string $action, array $params = []): string
    {
        return $this->application->url($action, $params);
    }

    /**
     * @throws RuntimeException
     */
    public function doInclude(string $resourceKey, object $scope): void
    {
        $resource = $this->application->getViewScriptManager()->getScript($resourceKey);
        $bound = $resource->bindTo($scope);

        $bound();
    }
}
