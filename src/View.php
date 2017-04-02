<?php

namespace mermshaus\fine;

/**
 *
 */
class View
{
    /**
     *
     * @var Application
     */
    private $app;

    /**
     *
     * @var string
     */
    private $script;

    /**
     *
     * @var array
     */
    private $values = array();

    /**
     *
     * @param Application $app
     * @param Config $config
     */
    public function __construct(Application $app, $script)
    {
        $this->app    = $app;
        $this->script = $script;
    }

    /**
     *
     * @param mixed $s
     * @return string
     */
    public function e($s)
    {
        return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
    }

    /**
     *
     * @param string $action
     * @param array $params
     * @return string
     */
    public function url($action, array $params = array())
    {
        return $this->app->url($action, $params);
    }

    /**
     *
     * @param string $resourceKey
     * @return string
     */
    private function doInclude($resourceKey)
    {
        $resource = $this->app->getViewScriptManager()->getScript($resourceKey);
        $bound = $resource->bindTo($this);

        $bound();
    }

    /**
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->values[$name] = $value;
    }

    /**
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (!isset($this->values[$name])) {
            return null;
        }

        return $this->values[$name];
    }

    /**
     *
     * @param array $vars
     * @return string
     */
    public function render(array $vars = array())
    {
        ob_start();

        $this->output($vars);

        return ob_get_clean();
    }

    /**
     *
     * @param array $vars
     */
    public function output(array $vars = array())
    {
        foreach ($vars as $key => $value) {
            $this->$key = $value;
        }

        $this->doInclude($this->script);
    }
}
