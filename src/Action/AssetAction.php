<?php

declare(strict_types=1);

namespace mermshaus\fine\Action;

use mermshaus\fine\AssetStore;
use mermshaus\fine\model\AbstractViewModel;

class AssetAction extends AbstractAction
{
    public function execute(): ?AbstractViewModel
    {
        // Action parameters: file

        $assetStore = new AssetStore();

        $file = $this->getGetString('file');

        $mtime = filemtime(__FILE__);

        // ETag
        if (
            isset($_SERVER['HTTP_IF_NONE_MATCH'])
            && trim($_SERVER['HTTP_IF_NONE_MATCH']) === md5($mtime . '-' . $file)
        ) {
            header('HTTP/1.1 304 Not Modified');

            return null;
        }

        // Modification date
        if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) === $mtime) {
            header('HTTP/1.1 304 Not Modified');

            return null;
        }

        $asset = $assetStore->get($file);

        header('Content-Type: ' . $asset->type . '; charset=UTF-8');
        header('ETag: ' . md5($mtime . '-' . $file));

        header('Cache-Control: public, max-age=2592000'); // 30 days

        $lastModified = gmdate('D, d M Y H:i:s \\G\\M\\T', $mtime);

        header('Last-Modified: ' . $lastModified);

        echo $asset->content;

        return null;
    }
}
