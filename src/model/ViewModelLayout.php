<?php

declare(strict_types=1);

namespace mermshaus\fine\model;

use mermshaus\fine\ApplicationApi;

final class ViewModelLayout extends AbstractViewModel
{
    private AbstractViewModel $contentView;

    private string $htmlId;

    private string $appVersion;

    public function __construct(
        ApplicationApi $api,
        string $script,
        AbstractViewModel $contentView,
        string $htmlId,
        string $appVersion
    ) {
        parent::__construct($api, $script);

        $this->contentView = $contentView;
        $this->htmlId = $htmlId;
        $this->appVersion = $appVersion;
    }

    public function getContentView(): AbstractViewModel
    {
        return $this->contentView;
    }

    public function getHtmlId(): string
    {
        return $this->htmlId;
    }

    public function getAppVersion(): string
    {
        return $this->appVersion;
    }
}
