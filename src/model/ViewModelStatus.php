<?php

declare(strict_types=1);

namespace mermshaus\fine\model;

use mermshaus\fine\ApplicationApi;

final class ViewModelStatus extends AbstractViewModel
{
    /**
     * @var array<string>
     */
    private array $prefixes;

    private string $output;

    private array $info;

    /**
     * @param array<string> $prefixes
     */
    public function __construct(ApplicationApi $api, string $script, array $prefixes, string $output, array $info)
    {
        parent::__construct($api, $script);

        $this->prefixes = $prefixes;
        $this->output = $output;
        $this->info = $info;
    }

    /**
     * @return array<string>
     */
    public function getPrefixes(): array
    {
        return $this->prefixes;
    }

    public function getOutput(): string
    {
        return $this->output;
    }

    public function getInfo(): array
    {
        return $this->info;
    }
}
