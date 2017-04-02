<?php

namespace mermshaus\fine;

/**
 *
 */
class FileCacheItem
{
    private $key;
    private $value;

    /**
     *
     * @param string $key
     * @param mixed $value
     */
    public function __construct($key, $value)
    {
        $this->key   = $key;
        $this->value = $value;
    }

    /**
     *
     * @return mixed
     */
    public function get()
    {
        return $this->value;
    }
}
