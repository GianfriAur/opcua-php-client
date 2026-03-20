<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

readonly class SubscriptionResult
{
    public function __construct(
        public int   $subscriptionId,
        public float $revisedPublishingInterval,
        public int   $revisedLifetimeCount,
        public int   $revisedMaxKeepAliveCount,
    )
    {
    }
}
