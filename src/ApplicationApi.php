<?php

namespace mermshaus\fine;

class ApplicationApi
{
    /**
     * @var Application
     */
    private $application;

    /**
     * @param Application $application
     */
    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * @param mixed $s
     *
     * @return string
     */
    public function e($s)
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param string $action
     * @param array  $params
     *
     * @return string
     */
    public function url($action, array $params = [])
    {
        return $this->application->url($action, $params);
    }

    /**
     * @param string $resourceKey
     * @param object $scope
     *
     * @throws \Exception
     */
    public function doInclude($resourceKey, $scope)
    {
        $resource = $this->application->getViewScriptManager()->getScript($resourceKey);
        $bound    = $resource->bindTo($scope);

        $bound();
    }
}
