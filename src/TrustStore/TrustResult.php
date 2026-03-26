<?php

declare(strict_types=1);

namespace PhpOpcua\Client\TrustStore;

use DateTimeImmutable;

/**
 * Result of a trust store certificate validation.
 */
readonly class TrustResult
{
    /**
     * @param bool $trusted Whether the certificate passed validation.
     * @param string $fingerprint SHA-1 fingerprint of the certificate (hex, colon-separated).
     * @param ?string $reason Human-readable reason for rejection. Null when trusted.
     * @param ?string $subject Certificate subject string (e.g. "CN=OPC UA Server").
     * @param ?DateTimeImmutable $notBefore Certificate validity start.
     * @param ?DateTimeImmutable $notAfter Certificate validity end.
     */
    public function __construct(
        public bool $trusted,
        public string $fingerprint,
        public ?string $reason = null,
        public ?string $subject = null,
        public ?DateTimeImmutable $notBefore = null,
        public ?DateTimeImmutable $notAfter = null,
    ) {
    }
}
