<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Types;

/**
 * Holds the result of an OPC UA ModifyMonitoredItems operation for a single item.
 *
 * @see \PhpOpcua\Client\Client\ManagesSubscriptionsTrait::modifyMonitoredItems()
 */
readonly class MonitoredItemModifyResult
{
    /**
     * @param int $statusCode
     * @param float $revisedSamplingInterval
     * @param int $revisedQueueSize
     */
    public function __construct(
        public int $statusCode,
        public float $revisedSamplingInterval,
        public int $revisedQueueSize,
    ) {
    }
}
