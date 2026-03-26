<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;

/**
 * Dispatched when an alarm notification includes a Severity field.
 *
 * @see AlarmEventReceived
 */
readonly class AlarmSeverityChanged
{
    public function __construct(
        public OpcUaClientInterface $client,
        public int $subscriptionId,
        public int $clientHandle,
        public ?string $sourceName = null,
        public int $severity = 0,
    ) {
    }
}
