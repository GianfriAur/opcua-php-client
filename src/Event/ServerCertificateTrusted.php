<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;

/**
 * Dispatched when a server certificate passes trust store validation.
 *
 * @see \PhpOpcua\Client\Client\ManagesTrustStoreTrait::validateServerCertificate()
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
