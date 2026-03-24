<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched after a monitored item has been deleted from a subscription.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesSubscriptionsTrait::deleteMonitoredItems()
 */
readonly class MonitoredItemDeleted
{
    public function __construct(
        public OpcUaClientInterface $client,
        public int                  $subscriptionId,
        public int                  $monitoredItemId,
        public int                  $statusCode,
    ) {
    }
}
