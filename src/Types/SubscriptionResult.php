<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

class SubscriptionResult
{
    public function __construct(
        public readonly int   $subscriptionId,
        public readonly float $revisedPublishingInterval,
        public readonly int   $revisedLifetimeCount,
        public readonly int   $revisedMaxKeepAliveCount,
    )
    {
    }
}
