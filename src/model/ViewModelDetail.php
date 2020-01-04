<?php

namespace mermshaus\fine\model;

use mermshaus\fine\ApplicationApi;

final class ViewModelDetail extends AbstractViewModel
{
    /**
     * @var string
     */
    private $album;

    /**
     * @var int
     */
    private $i;

    /**
     * @var string
     */
    private $imageUrl;

    /**
     * @var array
     */
    private $images;

    /**
     * @var Image
     */
    private $image;

    /**
     * @var string
     */
    private $prevImageUrl;

    /**
     * @var string
     */
    private $nextImageUrl;

    /**
     * @var int
     */
    private $page;

    /**
     * @var string
     */
    private $filename;

    /**
     * @param ApplicationApi $api
     * @param string         $script
     * @param string         $album
     * @param int            $i
     * @param string         $imageUrl
     * @param array          $images
     * @param Image          $image
     * @param string         $prevImageUrl
     * @param string         $nextImageUrl
     * @param int            $page
     * @param string         $filename
     */
    public function __construct(
        ApplicationApi $api,
        $script,
        $album,
        $i,
        $imageUrl,
        array $images,
        Image $image,
        $prevImageUrl,
        $nextImageUrl,
        $page,
        $filename
    ) {
        parent::__construct($api, $script);

        $this->album        = $album;
        $this->i            = $i;
        $this->imageUrl     = $imageUrl;
        $this->images       = $images;
        $this->image        = $image;
        $this->prevImageUrl = $prevImageUrl;
        $this->nextImageUrl = $nextImageUrl;
        $this->page         = $page;
        $this->filename     = $filename;
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
    public function getI()
    {
        return $this->i;
    }

    /**
     * @return string
     */
    public function getImageUrl()
    {
        return $this->imageUrl;
    }

    /**
     * @return array
     */
    public function getImages()
    {
        return $this->images;
    }

    /**
     * @return Image
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @return string
     */
    public function getPrevImageUrl()
    {
        return $this->prevImageUrl;
    }

    /**
     * @return string
     */
    public function getNextImageUrl()
    {
        return $this->nextImageUrl;
    }

    /**
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }
}
