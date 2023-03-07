<?php

declare(strict_types=1);

namespace mermshaus\fine\model;

use mermshaus\fine\ApplicationApi;

final class ViewModelAlbum extends AbstractViewModel
{
    private string $album;

    private int $activePage;

    private array $images;

    private int $imagesCount;

    private int $pagesCount;

    private int $previousPageNumber;

    private int $nextPageNumber;

    public function __construct(
        ApplicationApi $api,
        string $script,
        string $album,
        int $activePage,
        array $images,
        int $imagesCount,
        int $pagesCount,
        int $previousPageNumber,
        int $nextPageNumber
    ) {
        parent::__construct($api, $script);

        $this->album = $album;
        $this->activePage = $activePage;
        $this->images = $images;
        $this->imagesCount = $imagesCount;
        $this->pagesCount = $pagesCount;
        $this->previousPageNumber = $previousPageNumber;
        $this->nextPageNumber = $nextPageNumber;
    }

    public function getAlbum(): string
    {
        return $this->album;
    }

    public function getActivePage(): int
    {
        return $this->activePage;
    }

    public function getImages(): array
    {
        return $this->images;
    }

    public function getImagesCount(): int
    {
        return $this->imagesCount;
    }

    public function getPagesCount(): int
    {
        return $this->pagesCount;
    }

    public function getPreviousPageNumber(): int
    {
        return $this->previousPageNumber;
    }

    public function getNextPageNumber(): int
    {
        return $this->nextPageNumber;
    }
}
