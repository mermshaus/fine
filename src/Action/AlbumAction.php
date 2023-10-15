<?php

declare(strict_types=1);

namespace mermshaus\fine\Action;

use mermshaus\fine\model\AbstractViewModel;
use mermshaus\fine\model\ViewModelAlbum;
use RuntimeException;

final class AlbumAction extends AbstractAction
{
    public function execute(): ?AbstractViewModel
    {
        // Action parameters: album, page

        $album = $this->getGetString('album');
        $this->assertValidAlbum($album);

        $activePage = $this->getGetString('page', '1');
        /** @noinspection NotOptimalRegularExpressionsInspection */
        if (preg_match('/\\A[1-9][0-9]*\\z/', $activePage) === 0) {
            throw new RuntimeException('Value for page parameter must be >= 1.');
        }
        $activePage = (int) $activePage;

        $images = $this->getImages($this->config->albumPath . '/' . $album);

        $imagesCount = count($images);

        $images = array_slice($images, ($activePage - 1) * $this->config->imagesPerPage, $this->config->imagesPerPage);

        if (count($images) === 0) {
            throw new RuntimeException('No data for given parameters.');
        }

        $pagesCount = 0;

        do {
            $pagesCount++;
        } while ($pagesCount * $this->config->imagesPerPage < $imagesCount);

        $previousPageNumber = -1;
        $nextPageNumber = -1;

        if ($pagesCount > 1) {
            $previousPageNumber = $activePage - 1 > 0 ? $activePage - 1 : $pagesCount;
            $nextPageNumber = $activePage + 1 <= $pagesCount ? $activePage + 1 : 1;
        }

        return new ViewModelAlbum(
            $this->api,
            'album',
            $album,
            $activePage,
            $images,
            $imagesCount,
            $pagesCount,
            $previousPageNumber,
            $nextPageNumber,
        );
    }
}
