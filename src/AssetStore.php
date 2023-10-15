<?php

declare(strict_types=1);

namespace mermshaus\fine;

use mermshaus\fine\model\Asset;
use RuntimeException;

final class AssetStore
{
    public function get(string $key): Asset
    {
        $handle = fopen(__FILE__, 'rb');
        fseek($handle, __COMPILER_HALT_OFFSET__);
        $json = stream_get_contents($handle);
        fclose($handle);

        $jsonStore = json_decode($json, true);

        if (!array_key_exists($key, $jsonStore)) {
            throw new RuntimeException(sprintf('Unknown resource file "%s"', $key));
        }

        return new Asset(key: $key, type: $jsonStore[$key]['type'], content: $jsonStore[$key]['content']);
    }
}
