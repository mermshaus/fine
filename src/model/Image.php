<?php

namespace mermshaus\fine\model;

final class Image
{
    private $path;

    private $basename;

    private $isLoaded = false;

    private $fileSize;
    private $creationDate;
    private $width;
    private $height;
    private $filemdate;
    private $sortDate;

    /**
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path     = $path;
        $this->basename = pathinfo($this->path, PATHINFO_BASENAME);
    }

    private function load()
    {
        $this->fileSize     = filesize($this->path);
        $this->creationDate = null;
        $this->filemdate    = \DateTime::createFromFormat('U', (string) filemtime($this->path));

        list($this->width, $this->height) = getimagesize($this->path);

        set_error_handler(function () {
            // Discard EXIF warnings
        });
        $exifs = exif_read_data($this->path, 'IFD0', true, false);
        restore_error_handler();

        if ($exifs !== false && isset($exifs['IFD0']['DateTime'])) {
            $this->creationDate = \DateTime::createFromFormat(
                'Y:m:d H:i:s',
                $exifs['IFD0']['DateTime'],
                // @todo Try to determine correct TZ
                new \DateTimeZone('UTC')
            );

            if ($this->creationDate === false) {
                $this->creationDate = null;
            }
        }

        if ($this->creationDate !== null) {
            $this->sortDate = clone $this->creationDate;
        } else {
            $this->sortDate = clone $this->filemdate;
        }

        $this->isLoaded = true;

        #$GLOBALS['loadCalls']++;
    }

    /**
     * @return string
     */
    public function getBasename()
    {
        return $this->basename;
    }

    /**
     * @return int
     */
    public function getFileSize()
    {
        if ($this->fileSize === null) {
            $this->fileSize = filesize($this->path);
        }

        return $this->fileSize;
    }

    /**
     * @return \DateTime
     */
    public function getCreationDate()
    {
        if (!$this->isLoaded) {
            $this->load();
        }

        return $this->creationDate;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        if (!$this->isLoaded) {
            $this->load();
        }

        return $this->width;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        if (!$this->isLoaded) {
            $this->load();
        }

        return $this->height;
    }

    /**
     * @return \DateTime
     */
    public function getFilemdate()
    {
        if (!$this->isLoaded) {
            $this->load();
        }

        return $this->filemdate;
    }

    /**
     * @return \DateTime
     */
    public function getSortDate()
    {
        if (!$this->isLoaded) {
            $this->load();
        }

        return $this->sortDate;
    }
}
