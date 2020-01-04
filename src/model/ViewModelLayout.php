<?php

namespace mermshaus\fine\model;

use mermshaus\fine\ApplicationApi;

final class ViewModelLayout extends AbstractViewModel
{
    /**
     * @var AbstractViewModel
     */
    private $contentView;

    /**
     * @var string
     */
    private $htmlId;

    /**
     * @var string
     */
    private $appVersion;

    /**
     * @param ApplicationApi    $api
     * @param string            $script
     * @param AbstractViewModel $contentView
     * @param string            $htmlId
     * @param string            $appVersion
     */
    public function __construct(ApplicationApi $api, $script, AbstractViewModel $contentView, $htmlId, $appVersion)
    {
        parent::__construct($api, $script);

        $this->contentView = $contentView;
        $this->htmlId      = $htmlId;
        $this->appVersion  = $appVersion;
    }

    /**
     * @return AbstractViewModel
     */
    public function getContentView()
    {
        return $this->contentView;
    }

    /**
     * @return string
     */
    public function getHtmlId()
    {
        return $this->htmlId;
    }

    /**
     * @return string
     */
    public function getAppVersion()
    {
        return $this->appVersion;
    }
}
