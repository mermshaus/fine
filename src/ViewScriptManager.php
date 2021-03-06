<?php

namespace mermshaus\fine;

class ViewScriptManager
{
    /**
     * @var array
     */
    private $scripts = [];

    /**
     * @param string   $key
     * @param \Closure $content
     */
    public function addScript($key, \Closure $content)
    {
        $this->scripts[$key] = $content;
    }

    /**
     * @param string $key
     *
     * @return \Closure
     * @throws \RuntimeException
     */
    public function getScript($key)
    {
        if (!isset($this->scripts[$key])) {
            throw new \RuntimeException(sprintf('Script not found: "%s"', $key));
        }

        return $this->scripts[$key];
    }
}
