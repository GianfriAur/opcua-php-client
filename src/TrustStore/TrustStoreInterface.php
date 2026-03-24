<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\TrustStore;

/**
 * Contract for server certificate trust management.
 *
 * Implementations persist trusted and rejected certificates and validate
 * incoming server certificates against the trust store.
 */
interface TrustStoreInterface
{
    /**
     * @param string $certDer DER-encoded certificate bytes.
     * @return bool
     */
    public function isTrusted(string $certDer): bool;

    /**
     * @param string $certDer DER-encoded certificate bytes.
     * @return void
     */
    public function trust(string $certDer): void;

    /**
     * @param string $fingerprint SHA-1 fingerprint (hex, colon-separated or plain hex).
     * @return void
     */
    public function untrust(string $fingerprint): void;

    /**
     * @param string $certDer DER-encoded certificate bytes.
     * @return void
     */
    public function reject(string $certDer): void;

    /**
     * @return array<array{fingerprint: string, subject: ?string, notAfter: ?\DateTimeImmutable, path: string}>
     */
    public function getTrustedCertificates(): array;

    /**
     * @param string $certDer DER-encoded certificate bytes.
     * @param TrustPolicy $policy Validation level.
     * @param ?string $caCertPem Optional CA certificate in PEM format for chain validation.
     * @return TrustResult
     */
    public function validate(string $certDer, TrustPolicy $policy, ?string $caCertPem = null): TrustResult;
}
