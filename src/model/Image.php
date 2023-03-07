<?php

declare(strict_types=1);

namespace mermshaus\fine\model;

use DateTime;
use DateTimeZone;

final class Image
{
    private string $path;

    private string $basename;

    private bool $isLoaded = false;

    private ?int $fileSize = null;
    private ?DateTime $creationDate = null;
    private int $width;
    private int $height;
    private DateTime $filemdate;
    private DateTime $sortDate;

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->basename = pathinfo($this->path, PATHINFO_BASENAME);
    }

    private function load(): void
    {
        $this->fileSize = filesize($this->path);
        $this->creationDate = null;
        $this->filemdate = DateTime::createFromFormat('U', (string) filemtime($this->path));

        [$this->width, $this->height] = getimagesize($this->path);

        set_error_handler(function (int $errno, string $errstr): bool {
            // Discard EXIF warnings
            return true;
        });
        $exifs = exif_read_data($this->path, 'IFD0', true);
        restore_error_handler();

        if ($exifs !== false && isset($exifs['IFD0']['DateTime'])) {
            $this->creationDate = DateTime::createFromFormat(
                'Y:m:d H:i:s',
                $exifs['IFD0']['DateTime'],
                // @todo Try to determine correct TZ
                new DateTimeZone('UTC'),
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

        // $GLOBALS['loadCalls']++;
    }

    public function getBasename(): string
    {
        return $this->basename;
    }

    public function getFileSize(): int
    {
        if ($this->fileSize === null) {
            $this->fileSize = filesize($this->path);
        }

        return $this->fileSize;
    }

    public function getCreationDate(): DateTime
    {
        if (!$this->isLoaded) {
            $this->load();
        }

        return $this->creationDate;
    }

    public function getWidth(): int
    {
        if (!$this->isLoaded) {
            $this->load();
        }

        return $this->width;
    }

    public function getHeight(): int
    {
        if (!$this->isLoaded) {
            $this->load();
        }

        return $this->height;
    }

    public function getFilemdate(): DateTime
    {
        if (!$this->isLoaded) {
            $this->load();
        }

        return $this->filemdate;
    }

    public function getSortDate(): DateTime
    {
        if (!$this->isLoaded) {
            $this->load();
        }

        return $this->sortDate;
    }
}
