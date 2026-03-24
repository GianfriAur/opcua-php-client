<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched after every publish response is decoded, regardless of notification content.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesSubscriptionsTrait::publish()
 */
readonly class PublishResponseReceived
{
    public function __construct(
        public OpcUaClientInterface $client,
        public int $subscriptionId,
        public int $sequenceNumber,
        public int $notificationCount,
        public bool $moreNotifications,
    ) {
    }
}
