<?php

namespace mermshaus\fine\model;

use mermshaus\fine\ApplicationApi;

final class ViewModelAlbum extends AbstractViewModel
{
    /**
     * @var string
     */
    private $album;

    /**
     * @var int
     */
    private $activePage;

    /**
     * @var array
     */
    private $images;

    /**
     * @var int
     */
    private $imagesCount;

    /**
     * @var int
     */
    private $pagesCount;

    /**
     * @var int
     */
    private $previousPageNumber;

    /**
     * @var int
     */
    private $nextPageNumber;

    /**
     * @var bool
     */
    private $isInSingleAlbumMode;

    /**
     * @param ApplicationApi $api
     * @param string         $script
     * @param string         $album
     * @param int            $activePage
     * @param array          $images
     * @param int            $imagesCount
     * @param int            $pagesCount
     * @param int            $previousPageNumber
     * @param int            $nextPageNumber
     * @param bool           $isInSingleAlbumMode
     */
    public function __construct(
        ApplicationApi $api,
        $script,
        $album,
        $activePage,
        array $images,
        $imagesCount,
        $pagesCount,
        $previousPageNumber,
        $nextPageNumber,
        $isInSingleAlbumMode
    ) {
        parent::__construct($api, $script);

        $this->album               = $album;
        $this->activePage          = $activePage;
        $this->images              = $images;
        $this->imagesCount         = $imagesCount;
        $this->pagesCount          = $pagesCount;
        $this->previousPageNumber  = $previousPageNumber;
        $this->nextPageNumber      = $nextPageNumber;
        $this->isInSingleAlbumMode = $isInSingleAlbumMode;
    }

    /**
     * @return string
     */
    public function getAlbum()
    {
        return $this->album;
    }

    /**
     * @return int
     */
    public function getActivePage()
    {
        return $this->activePage;
    }

    /**
     * @return array
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @return int
     */
    public function getImagesCount()
    {
        return $this->imagesCount;
    }

    /**
     * @return int
     */
    public function getPagesCount()
    {
        return $this->pagesCount;
    }

    /**
     * @return int
     */
    public function getPreviousPageNumber()
    {
        return $this->previousPageNumber;
    }

    /**
     * @return int
     */
    public function getNextPageNumber()
    {
        return $this->nextPageNumber;
    }

    /**
     * @return bool
     */
    public function getIsInSingleAlbumMode()
    {
        return $this->isInSingleAlbumMode;
    }
}
