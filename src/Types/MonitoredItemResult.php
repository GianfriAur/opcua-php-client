<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

/**
 * Holds the result of an OPC UA CreateMonitoredItems operation for a single item.
 *
 * @see \Gianfriaur\OpcuaPhpClient\OpcuaClient::createMonitoredItems()
 */
readonly class MonitoredItemResult
{
    /**
     * @param int $statusCode
     * @param int $monitoredItemId
     * @param float $revisedSamplingInterval
     * @param int $revisedQueueSize
     */
    public function __construct(
        public int $statusCode,
        public int $monitoredItemId,
        public float $revisedSamplingInterval,
        public int $revisedQueueSize,
    ) {
    }
}
