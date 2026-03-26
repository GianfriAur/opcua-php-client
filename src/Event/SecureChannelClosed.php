<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;

/**
 * Dispatched after the secure channel has been closed.
 *
 * @see \PhpOpcua\Client\Client\ManagesSecureChannelTrait::closeSecureChannel()
 */
readonly class SecureChannelClosed
{
    public function __construct(
        public OpcUaClientInterface $client,
        public int $channelId,
    ) {
    }
}
