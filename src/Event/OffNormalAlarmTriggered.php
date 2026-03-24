<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched when an event notification originates from an OffNormalAlarm or DiscreteAlarm type.
 *
 * @see AlarmEventReceived
 */
readonly class OffNormalAlarmTriggered
{
    public function __construct(
        public OpcUaClientInterface $client,
        public int $subscriptionId,
        public int $clientHandle,
        public ?string $sourceName = null,
        public ?int $severity = null,
    ) {
    }
}
