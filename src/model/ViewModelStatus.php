<?php

namespace mermshaus\fine\model;

use mermshaus\fine\ApplicationApi;

final class ViewModelStatus extends AbstractViewModel
{
    /**
     * @var array
     */
    private $prefixes;

    /**
     * @var string
     */
    private $output;

    /**
     * @var array
     */
    private $info;

    /**
     * @param ApplicationApi $api
     * @param string         $script
     * @param array          $prefixes
     * @param string         $output
     * @param array          $info
     */
    public function __construct(ApplicationApi $api, $script, array $prefixes, $output, array $info)
    {
        parent::__construct($api, $script);

        $this->prefixes = $prefixes;
        $this->output   = $output;
        $this->info     = $info;
    }

    /**
     * @return array
     */
    public function getPrefixes()
    {
        return $this->prefixes;
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return array
     */
    public function getInfo()
    {
        return $this->info;
    }
}
