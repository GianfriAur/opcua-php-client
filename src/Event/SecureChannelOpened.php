<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;
use Gianfriaur\OpcuaPhpClient\Security\SecurityMode;
use Gianfriaur\OpcuaPhpClient\Security\SecurityPolicy;

/**
 * Dispatched after a secure channel has been opened with the server.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesSecureChannelTrait::openSecureChannel()
 */
readonly class SecureChannelOpened
{
    public function __construct(
        public OpcUaClientInterface $client,
        public int $channelId,
        public SecurityPolicy $securityPolicy,
        public SecurityMode $securityMode,
    ) {
    }
}
