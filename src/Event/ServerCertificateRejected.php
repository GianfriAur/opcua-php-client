<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;

/**
 * Dispatched when a server certificate is rejected by the trust store.
 *
 * @see \PhpOpcua\Client\Client\ManagesTrustStoreTrait::validateServerCertificate()
 */
readonly class ServerCertificateRejected
{
    public function __construct(
        public OpcUaClientInterface $client,
        public string $fingerprint,
        public ?string $reason = null,
        public ?string $subject = null,
    ) {
    }
}
