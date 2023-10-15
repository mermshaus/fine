<?php

declare(strict_types=1);

namespace mermshaus\fine;

use Exception;
use mermshaus\fine\Action\AlbumAction;
use mermshaus\fine\Action\AssetAction;
use mermshaus\fine\Action\DetailAction;
use mermshaus\fine\Action\GifAction;
use mermshaus\fine\Action\ImageAction;
use mermshaus\fine\Action\IndexAction;
use mermshaus\fine\Action\RandomAction;
use mermshaus\fine\Action\StatusAction;
use mermshaus\fine\model\AbstractViewModel;
use mermshaus\fine\model\ViewModelLayout;
use RuntimeException;
use Throwable;

final class Application
{
    public const VERSION = '1.0.0-dev';

    private ApplicationApi $api;

    public function __construct(
        readonly Config $config,
        readonly ViewScriptManager $viewScriptManager,
        readonly FileCache $cache
    ) {
        $this->api = new ApplicationApi($viewScriptManager);

        // We're not using a type hint here in order to support both Exceptions and Throwables
        set_exception_handler(function ($e) {
            /** @var Exception|Throwable $e */
            header('HTTP/1.1 404 Not Found');
            header('Content-Type: text/plain; charset=UTF-8');
            echo $e->getMessage() . "\n";
        });
    }

    /**
     * @throws RuntimeException
     */
    public function run(): void
    {
        $action = isset($_GET['action']) && is_string($_GET['action']) ? $_GET['action'] : 'index';

        $actionObject = match ($action) {
            'asset' => new AssetAction($this->api, $this->config),
            'album' => new AlbumAction($this->api, $this->config),
            'detail' => new DetailAction($this->api, $this->config),
            'gif' => new GifAction($this->api, $this->config),
            'image' => new ImageAction($this->api, $this->config, $this->cache),
            'index' => new IndexAction($this->api, $this->config, $this->cache),
            'random' => new RandomAction($this->api, $this->config),
            'status' => new StatusAction($this->api, $this->config, $this->cache),
            default => throw new RuntimeException(sprintf('Unknown action: "%s"', $action)),
        };

        $viewModel = $actionObject->execute();

        if (!$viewModel instanceof AbstractViewModel) {
            return;
        }

        header('Content-Type: text/html; charset=utf-8');

        $layout = new ViewModelLayout($this->api, 'layout', $viewModel, 'page-' . $action, self::VERSION);

        $layout->output();
    }
}
