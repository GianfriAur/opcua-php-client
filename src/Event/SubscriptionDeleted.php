<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched after a subscription has been deleted from the server.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesSubscriptionsTrait::deleteSubscription()
 */
readonly class SubscriptionDeleted
{
    public function __construct(
        public OpcUaClientInterface $client,
        public int $subscriptionId,
        public int $statusCode,
    ) {
    }
}
