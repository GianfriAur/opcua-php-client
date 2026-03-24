<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched after a subscription has been created on the server.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesSubscriptionsTrait::createSubscription()
 */
readonly class SubscriptionCreated
{
    public function __construct(
        public OpcUaClientInterface $client,
        public int $subscriptionId,
        public float $revisedPublishingInterval,
        public int $revisedLifetimeCount,
        public int $revisedMaxKeepAliveCount,
    ) {
    }
}
