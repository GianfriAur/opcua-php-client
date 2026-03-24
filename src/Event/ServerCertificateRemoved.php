<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched when a server certificate is removed from the trust store.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesTrustStoreTrait::untrustCertificate()
 */
readonly class ServerCertificateRemoved
{
    public function __construct(
        public OpcUaClientInterface $client,
        public string $fingerprint,
    ) {
    }
}
