<?php

declare(strict_types=1);

namespace mermshaus\fine\Action;

use mermshaus\fine\model\AbstractViewModel;

final class RandomAction extends AbstractAction
{
    public function execute(): ?AbstractViewModel
    {
        // Action parameters: album

        $album = $this->getGetString('album');
        $this->assertValidAlbum($album);

        $images = $this->getImages($this->config->albumPath . '/' . $album);

        $image = $images[array_rand($images)];

        $url = $this->api->url('detail', ['album' => $album, 'filename' => $image->getBasename()]);

        $this->doRedirect($url);

        return null;
    }

    private function doRedirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }
}
