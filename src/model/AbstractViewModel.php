<?php

namespace mermshaus\fine\model;

use mermshaus\fine\ApplicationApi;

abstract class AbstractViewModel
{
    /**
     * @var ApplicationApi
     */
    private $api;

    /**
     * @var string
     */
    private $script;

    /**
     * @param ApplicationApi $api
     * @param string         $script
     */
    public function __construct(ApplicationApi $api, $script)
    {
        $this->api    = $api;
        $this->script = $script;
    }

    /**
     * @param mixed $s
     *
     * @return string
     */
    public function e($s)
    {
        return $this->api->e($s);
    }

    /**
     * @param string $action
     * @param array  $params
     *
     * @return string
     */
    public function url($action, array $params = [])
    {
        return $this->api->url($action, $params);
    }

    /**
     * @param string $resourceKey
     *
     * @throws \RuntimeException
     */
    private function doInclude($resourceKey)
    {
        $this->api->doInclude($resourceKey, $this);
    }

    /**
     * @param array $vars
     *
     * @return string
     * @throws \RuntimeException
     */
    public function render(array $vars = [])
    {
        ob_start();

        $this->output($vars);

        return ob_get_clean();
    }

    /**
     * @param array $vars
     *
     * @throws \RuntimeException
     */
    public function output(array $vars = [])
    {
        foreach ($vars as $key => $value) {
            $this->$key = $value;
        }

        $this->doInclude($this->script);
    }
}
