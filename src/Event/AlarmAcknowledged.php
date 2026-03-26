<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;

/**
 * Dispatched when an alarm's AckedState transitions to true.
 *
 * @see AlarmEventReceived
 */
readonly class AlarmAcknowledged
{
    public function __construct(
        public OpcUaClientInterface $client,
        public int $subscriptionId,
        public int $clientHandle,
        public ?string $sourceName = null,
    ) {
    }
}
