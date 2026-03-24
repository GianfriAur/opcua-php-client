<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched when an alarm notification includes a Severity field.
 *
 * @see AlarmEventReceived
 */
readonly class AlarmSeverityChanged
{
    public function __construct(
        public OpcUaClientInterface $client,
        public int                  $subscriptionId,
        public int                  $clientHandle,
        public ?string $sourceName = null,
        public int     $severity = 0,
    ) {
    }
}
