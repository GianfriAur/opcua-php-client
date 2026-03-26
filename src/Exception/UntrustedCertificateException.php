<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Exception;

/**
 * Thrown when a server certificate is not trusted by the configured trust store.
 *
 * Contains the certificate fingerprint and raw DER bytes for programmatic handling.
 */
class UntrustedCertificateException extends SecurityException
{
    /**
     * @param string $fingerprint SHA-1 fingerprint (hex, colon-separated).
     * @param string $certDer Raw DER-encoded certificate bytes.
     * @param string $message Human-readable error message.
     */
    public function __construct(
        public readonly string $fingerprint,
        public readonly string $certDer,
        string $message,
    ) {
        parent::__construct($message);
    }
}
