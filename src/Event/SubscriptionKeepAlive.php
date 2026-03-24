<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched when a publish response contains no notifications (keep-alive).
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesSubscriptionsTrait::publish()
 */
readonly class SubscriptionKeepAlive
{
    public function __construct(
        public OpcUaClientInterface $client,
        public int $subscriptionId,
        public int $sequenceNumber,
    ) {
    }
}
