<?php

declare(strict_types=1);

namespace mermshaus\fine\model;

use mermshaus\fine\ApplicationApi;

final class ViewModelIndex extends AbstractViewModel
{
    private string $path;

    private array $navigation;

    private array $elements;

    private bool $canUseCache;

    private string $pageTitle;

    public function __construct(
        ApplicationApi $applicationApi,
        string $script,
        string $path,
        array $navigation,
        array $elements,
        bool $canUseCache,
        string $pageTitle
    ) {
        parent::__construct($applicationApi, $script);

        $this->path = $path;
        $this->navigation = $navigation;
        $this->elements = $elements;
        $this->canUseCache = $canUseCache;
        $this->pageTitle = $pageTitle;
    }

    public function getNavigation(): array
    {
        return $this->navigation;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getElements(): array
    {
        return $this->elements;
    }

    public function getCanUseCache(): bool
    {
        return $this->canUseCache;
    }

    public function getPageTitle(): string
    {
        return $this->pageTitle;
    }
}
