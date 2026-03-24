<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched when a server certificate passes trust store validation.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesTrustStoreTrait::validateServerCertificate()
 */
readonly class ServerCertificateTrusted
{
    public function __construct(
        public OpcUaClientInterface $client,
        public string $fingerprint,
        public ?string $subject = null,
    ) {
    }
}
