<?php

declare(strict_types=1);

namespace mermshaus\fine\model;

use mermshaus\fine\ApplicationApi;

final class ViewModelDetail extends AbstractViewModel
{
    private string $album;

    private int $i;

    private string $imageUrl;

    /**
     * @var array<Image>
     */
    private array $images;

    private ?Image $image;

    private string $prevImageUrl;

    private string $nextImageUrl;

    private int $page;

    private string $filename;

    /**
     * @param array<Image> $images
     */
    public function __construct(
        ApplicationApi $api,
        string $script,
        string $album,
        int $i,
        string $imageUrl,
        array $images,
        ?Image $image,
        string $prevImageUrl,
        string $nextImageUrl,
        int $page,
        string $filename
    ) {
        parent::__construct($api, $script);

        $this->album = $album;
        $this->i = $i;
        $this->imageUrl = $imageUrl;
        $this->images = $images;
        $this->image = $image;
        $this->prevImageUrl = $prevImageUrl;
        $this->nextImageUrl = $nextImageUrl;
        $this->page = $page;
        $this->filename = $filename;
    }

    public function getAlbum(): string
    {
        return $this->album;
    }

    public function getI(): int
    {
        return $this->i;
    }

    public function getImageUrl(): string
    {
        return $this->imageUrl;
    }

    /**
     * @return array<Image>
     */
    public function getImages(): array
    {
        return $this->images;
    }

    public function getImage(): ?Image
    {
        return $this->image;
    }

    public function getPrevImageUrl(): string
    {
        return $this->prevImageUrl;
    }

    public function getNextImageUrl(): string
    {
        return $this->nextImageUrl;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }
}
