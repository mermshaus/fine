<?php

declare(strict_types=1);

namespace mermshaus\fine\model;

use mermshaus\fine\ApplicationApi;

final class ViewModelIndex extends AbstractViewModel
{
    private string $path;

    /**
     * @var array<NavigationItem>
     */
    private array $navigation;

    /**
     * @var array<Element>
     */
    private array $elements;

    private bool $canUseCache;

    private string $pageTitle;

    /**
     * @param array<NavigationItem> $navigation
     * @param array<Element>        $elements
     */
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

    /**
     * @return array<NavigationItem>
     */
    public function getNavigation(): array
    {
        return $this->navigation;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @return array<Element>
     */
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
