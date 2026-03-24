<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;
use Gianfriaur\OpcuaPhpClient\Types\DataValue;

/**
 * Dispatched for each data change notification received from a publish response.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesSubscriptionsTrait::publish()
 */
readonly class DataChangeReceived
{
    public function __construct(
        public OpcUaClientInterface $client,
        public int $subscriptionId,
        public int $sequenceNumber,
        public int $clientHandle,
        public DataValue $dataValue,
    ) {
    }
}
