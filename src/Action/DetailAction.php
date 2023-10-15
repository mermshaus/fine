<?php

declare(strict_types=1);

namespace mermshaus\fine\Action;

use mermshaus\fine\model\AbstractViewModel;
use mermshaus\fine\model\ViewModelDetail;

final class DetailAction extends AbstractAction
{
    public function execute(): ?AbstractViewModel
    {
        // Action parameters: path

        $path = $this->getGetString('path');

        $absPath = $this->config->albumPath . '/' . $path;
        $dirname = dirname($absPath);
        $pathDirname = dirname($path);
        $basename = basename($absPath);

        if ($pathDirname === '.') {
            $pathDirname = '';
        }

        if (pathinfo($absPath, PATHINFO_EXTENSION) === 'gif') {
            $imageUrl = $this->api->url('gif', ['path' => $path]);
        } else {
            $imageUrl = $this->api->url('image', ['path' => $path, 'element' => 'large']);
        }

        $images = $this->getImages($dirname);

        $i = 0;
        foreach ($images as $image) {
            if ($image->getBasename() === $basename) {
                break;
            }
            $i++;
        }

        $image = null;

        $page = 1;

        while ($i >= $page * $this->config->imagesPerPage) {
            $page++;
        }

        $prevImageUrl = '';
        $nextImageUrl = '';

        if (count($images) > 1) {
            if ($i - 1 >= 0) {
                $prevImageUrl = $this->api->url('detail', [
                    'path' => $this->combinePath([$pathDirname, $images[$i - 1]->getBasename()]),
                ]);
            } else {
                $prevImageUrl = $this->api->url('detail', [
                    'path' => $this->combinePath([$pathDirname, $images[count($images) - 1]->getBasename()]),
                ]);
            }

            if ($i + 1 < count($images)) {
                $nextImageUrl = $this->api->url('detail', [
                    'path' => $this->combinePath([$pathDirname, $images[$i + 1]->getBasename()]),
                ]);
            } else {
                $nextImageUrl = $this->api->url('detail', [
                    'path' => $this->combinePath([$pathDirname, $images[0]->getBasename()]),
                ]);
            }
        }

        return new ViewModelDetail(
            $this->api,
            'detail',
            $pathDirname,
            $i,
            $imageUrl,
            $images,
            $image,
            $prevImageUrl,
            $nextImageUrl,
            $page,
            $path,
        );
    }
}
