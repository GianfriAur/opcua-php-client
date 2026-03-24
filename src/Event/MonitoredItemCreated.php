<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;

/**
 * Dispatched after a monitored item has been created within a subscription.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesSubscriptionsTrait::createMonitoredItems()
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesSubscriptionsTrait::createEventMonitoredItem()
 */
readonly class MonitoredItemCreated
{
    public function __construct(
        public OpcUaClientInterface $client,
        public int $subscriptionId,
        public int $monitoredItemId,
        public NodeId $nodeId,
        public int $statusCode,
    ) {
    }
}
