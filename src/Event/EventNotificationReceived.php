<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;
use Gianfriaur\OpcuaPhpClient\Types\Variant;

/**
 * Dispatched for each event notification received from a publish response.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesSubscriptionsTrait::publish()
 */
readonly class EventNotificationReceived
{
    /**
     * @param OpcUaClientInterface $client
     * @param int $subscriptionId
     * @param int $sequenceNumber
     * @param int $clientHandle
     * @param Variant[] $eventFields
     */
    public function __construct(
        public OpcUaClientInterface $client,
        public int                  $subscriptionId,
        public int                  $sequenceNumber,
        public int                  $clientHandle,
        public array                $eventFields,
    ) {
    }
}
