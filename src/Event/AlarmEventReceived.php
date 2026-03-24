<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use DateTimeImmutable;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;
use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;
use Gianfriaur\OpcuaPhpClient\Types\Variant;

/**
 * Dispatched for every event notification that contains alarm-related fields.
 *
 * This is the generic alarm event — specific alarm events ({@see AlarmActivated},
 * {@see AlarmDeactivated}, etc.) may also be dispatched based on field analysis.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesSubscriptionsTrait::publish()
 */
readonly class AlarmEventReceived
{
    /**
     * @param OpcUaClientInterface $client
     * @param int $subscriptionId
     * @param int $clientHandle
     * @param Variant[] $eventFields
     * @param ?int $severity
     * @param ?string $sourceName
     * @param ?string $message
     * @param ?NodeId $eventType
     * @param ?DateTimeImmutable $time
     */
    public function __construct(
        public OpcUaClientInterface $client,
        public int                $subscriptionId,
        public int                $clientHandle,
        public array              $eventFields,
        public ?int               $severity = null,
        public ?string            $sourceName = null,
        public ?string            $message = null,
        public ?NodeId            $eventType = null,
        public ?DateTimeImmutable $time = null,
    ) {
    }
}
