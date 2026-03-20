<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

class PublishResult
{
    /**
     * @param int $subscriptionId
     * @param int $sequenceNumber
     * @param bool $moreNotifications
     * @param array $notifications
     * @param int[] $availableSequenceNumbers
     */
    public function __construct(
        public readonly int   $subscriptionId,
        public readonly int   $sequenceNumber,
        public readonly bool  $moreNotifications,
        public readonly array $notifications,
        public readonly array $availableSequenceNumbers,
    )
    {
    }
}
