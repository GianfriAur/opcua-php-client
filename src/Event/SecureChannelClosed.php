<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched after the secure channel has been closed.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesSecureChannelTrait::closeSecureChannel()
 */
readonly class SecureChannelClosed
{
    public function __construct(
        public OpcUaClientInterface $client,
        public int                  $channelId,
    ) {
    }
}
