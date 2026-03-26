<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;
use PhpOpcua\Client\Security\SecurityMode;
use PhpOpcua\Client\Security\SecurityPolicy;

/**
 * Dispatched after a secure channel has been opened with the server.
 *
 * @see \PhpOpcua\Client\Client\ManagesSecureChannelTrait::openSecureChannel()
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
