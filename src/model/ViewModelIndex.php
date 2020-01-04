<?php

namespace mermshaus\fine\model;

use mermshaus\fine\ApplicationApi;

final class ViewModelIndex extends AbstractViewModel
{
    /**
     * @var array
     */
    private $albums;

    /**
     * @var array
     */
    private $coverImages;

    /**
     * @var bool
     */
    private $canUseCache;

    /**
     * @param ApplicationApi $applicationApi
     * @param string         $script
     * @param array          $albums
     * @param array          $coverImages
     * @param bool           $canUseCache
     */
    public function __construct(ApplicationApi $applicationApi, $script, array $albums, array $coverImages, $canUseCache)
    {
        parent::__construct($applicationApi, $script);

        $this->albums      = $albums;
        $this->coverImages = $coverImages;
        $this->canUseCache = $canUseCache;
    }

    /**
     * @return array
     */
    public function getAlbums()
    {
        return $this->albums;
    }

    /**
     * @return array
     */
    public function getCoverImages()
    {
        return $this->coverImages;
    }

    /**
     * @return bool
     */
    public function getCanUseCache()
    {
        return $this->canUseCache;
    }
}
