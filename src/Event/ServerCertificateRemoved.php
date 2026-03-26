<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;

/**
 * Dispatched when a server certificate is removed from the trust store.
 *
 * @see \PhpOpcua\Client\Client\ManagesTrustStoreTrait::untrustCertificate()
 */
readonly class ServerCertificateRemoved
{
    public function __construct(
        public OpcUaClientInterface $client,
        public string $fingerprint,
    ) {
    }
}
