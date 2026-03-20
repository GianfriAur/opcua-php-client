<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

readonly class MonitoredItemResult
{
    public function __construct(
        public int   $statusCode,
        public int   $monitoredItemId,
        public float $revisedSamplingInterval,
        public int   $revisedQueueSize,
    )
    {
    }
}
