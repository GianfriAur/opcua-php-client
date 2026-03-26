<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;

/**
 * Dispatched when a server certificate is manually added to the trust store.
 *
 * @see \PhpOpcua\Client\Client\ManagesTrustStoreTrait::trustCertificate()
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
