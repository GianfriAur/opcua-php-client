<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched after a subscription has been transferred from another session.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesSubscriptionsTrait::transferSubscriptions()
 */
readonly class SubscriptionTransferred
{
    public function __construct(
        public OpcUaClientInterface $client,
        public int $subscriptionId,
        public int $statusCode,
    ) {
    }
}
