<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched when an alarm transitions to the inactive state.
 *
 * Deduced from the ActiveState field being false in the event notification.
 *
 * @see AlarmEventReceived
 */
readonly class AlarmDeactivated
{
    public function __construct(
        public OpcUaClientInterface $client,
        public int $subscriptionId,
        public int $clientHandle,
        public ?string $sourceName = null,
        public ?string $message = null,
    ) {
    }
}
