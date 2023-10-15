<?php

declare(strict_types=1);

namespace mermshaus\fine\Action;

use mermshaus\fine\model\AbstractViewModel;

final class GifAction extends AbstractAction
{
    public function execute(): ?AbstractViewModel
    {
        // Action parameters: path

        $path = $this->getGetString('path');
        $fullPath = 'albums/' . $path;

        // $lastModified = gmdate('D, d M Y H:i:s \\G\\M\\T');
        // $this->sendImageHeaders('image/jpeg', $lastModified, null, $prefix . '-' . $basename);
        readfile($fullPath);

        return null;
    }
}
