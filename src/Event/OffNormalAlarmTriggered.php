<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;

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
