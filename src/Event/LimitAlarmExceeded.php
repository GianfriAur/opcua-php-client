<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched when an event notification originates from a LimitAlarm type.
 *
 * The limit state indicates which threshold was crossed (HighHigh, High, Low, LowLow).
 *
 * @see AlarmEventReceived
 */
readonly class LimitAlarmExceeded
{
    public function __construct(
        public OpcUaClientInterface $client,
        public int                  $subscriptionId,
        public int                  $clientHandle,
        public ?string $sourceName = null,
        public ?string $limitState = null,
        public ?int    $severity = null,
    ) {
    }
}
