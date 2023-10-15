<?php

declare(strict_types=1);

namespace mermshaus\fine;

use RuntimeException;

final class ApplicationApi
{
    private ViewScriptManager $viewScriptManager;

    public function __construct(ViewScriptManager $viewScriptManager)
    {
        $this->viewScriptManager = $viewScriptManager;
    }

    public function e(mixed $s): string
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }

    public function url(string $action, array $params = []): string
    {
        if (
            isset($params['album'])
            && $params['album'] === ''
            && in_array($action, ['album', 'detail', 'image'], true)
        ) {
            unset($params['album']);
        }

        $url = './';

        if (basename(__FILE__) !== 'index.php') {
            $url = basename(__FILE__);
        }

        if ($action !== 'index') {
            $params = array_merge(['action' => $action], $params);
        }

        if ($action === 'index' && isset($params['path']) && $params['path'] === '') {
            unset($params['path']);
        }

        if (count($params) > 0) {
            $url .= '?' . http_build_query($params);
        }

        return $url;
    }

    /**
     * @throws RuntimeException
     */
    public function doInclude(string $resourceKey, object $scope): void
    {
        $resource = $this->viewScriptManager->getScript($resourceKey);
        $bound = $resource->bindTo($scope);

        $bound();
    }
}
