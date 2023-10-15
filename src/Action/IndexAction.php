<?php

declare(strict_types=1);

namespace mermshaus\fine\Action;

use mermshaus\fine\ApplicationApi;
use mermshaus\fine\Config;
use mermshaus\fine\FileCache;
use mermshaus\fine\model\AbstractViewModel;
use mermshaus\fine\model\NavigationItem;
use mermshaus\fine\model\ViewModelIndex;

final class IndexAction extends AbstractAction
{
    public function __construct(ApplicationApi $api, Config $config, private readonly FileCache $cache)
    {
        parent::__construct($api, $config);
    }

    public function execute(): ?AbstractViewModel
    {
        // Action parameters: path

        $path = $this->getGetString('path', '/');

        $elements = $this->getElements($path);

        $navigation = [];

        $pathParts = $path !== '' ? explode('/', $path) : [];

        if (count($pathParts) > 0) {
            $navigation[] = new NavigationItem(title: '/', url: $this->api->url('index'));

            $curPath = '';

            foreach ($pathParts as $pathPart) {
                if ($pathPart === '') {
                    continue;
                }

                $curPath .= $curPath === '' ? $pathPart : '/' . $pathPart;

                $navigation[] = new NavigationItem(
                    title: $pathPart,
                    url: $this->api->url('index', ['path' => '/' . $curPath]),
                );
            }
        }

        $navigation = array_slice($navigation, 0, count($navigation) - 1);

        return new ViewModelIndex(
            $this->api,
            'index',
            $path,
            $navigation,
            $elements,
            $this->cache->isWritable(),
            basename($path),
        );
    }
}
