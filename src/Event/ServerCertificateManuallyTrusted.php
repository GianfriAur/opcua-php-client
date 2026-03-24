<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched when a server certificate is manually added to the trust store.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesTrustStoreTrait::trustCertificate()
 */
readonly class ServerCertificateManuallyTrusted
{
    public function __construct(
        public OpcUaClientInterface $client,
        public string $fingerprint,
        public ?string $subject = null,
    ) {
    }
}
