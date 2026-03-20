<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

class MonitoredItemResult
{
    public function __construct(
        public readonly int   $statusCode,
        public readonly int   $monitoredItemId,
        public readonly float $revisedSamplingInterval,
        public readonly int   $revisedQueueSize,
    )
    {
    }
}
