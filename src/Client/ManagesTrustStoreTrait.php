<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Client;

use Gianfriaur\OpcuaPhpClient\Event\ServerCertificateAutoAccepted;
use Gianfriaur\OpcuaPhpClient\Event\ServerCertificateManuallyTrusted;
use Gianfriaur\OpcuaPhpClient\Event\ServerCertificateRejected;
use Gianfriaur\OpcuaPhpClient\Event\ServerCertificateRemoved;
use Gianfriaur\OpcuaPhpClient\Event\ServerCertificateTrusted;
use Gianfriaur\OpcuaPhpClient\Exception\UntrustedCertificateException;
use Gianfriaur\OpcuaPhpClient\TrustStore\TrustPolicy;
use Gianfriaur\OpcuaPhpClient\TrustStore\TrustStoreInterface;

/**
 * Provides server certificate trust management for the OPC UA client.
 *
 * When a trust store and policy are configured, the client validates the server certificate
 * during connection. Pass `setTrustPolicy(null)` to disable validation entirely (default).
 */
trait ManagesTrustStoreTrait
{
    /**
     * @var ?TrustStoreInterface
     */
    private ?TrustStoreInterface $trustStore = null;

    /**
     * @var ?TrustPolicy
     */
    private ?TrustPolicy $trustPolicy = null;

    /**
     * @var bool
     */
    private bool $autoAcceptEnabled = false;

    /**
     * @var bool
     */
    private bool $autoAcceptForce = false;

    /**
     * Set the trust store for server certificate validation.
     *
     * @param ?TrustStoreInterface $trustStore The trust store, or null to remove.
     * @return self
     */
    public function setTrustStore(?TrustStoreInterface $trustStore): self
    {
        $this->trustStore = $trustStore;

        return $this;
    }

    /**
     * Get the current trust store.
     *
     * @return ?TrustStoreInterface
     */
    public function getTrustStore(): ?TrustStoreInterface
    {
        return $this->trustStore;
    }

    /**
     * Set the trust validation policy. Pass null to disable trust validation entirely.
     *
     * @param ?TrustPolicy $policy
     * @return self
     */
    public function setTrustPolicy(?TrustPolicy $policy): self
    {
        $this->trustPolicy = $policy;

        return $this;
    }

    /**
     * Get the current trust policy. Null means validation is disabled.
     *
     * @return ?TrustPolicy
     */
    public function getTrustPolicy(): ?TrustPolicy
    {
        return $this->trustPolicy;
    }

    /**
     * Enable or disable auto-accept (TOFU) for unknown server certificates.
     *
     * When enabled, unknown certificates are accepted and saved to the trust store.
     * However, if a different certificate is already trusted for the same server,
     * the connection fails — auto-accept only works for genuinely new certificates.
     *
     * @param bool $enabled
     * @return self
     */
    public function autoAccept(bool $enabled = true, bool $force = false): self
    {
        $this->autoAcceptEnabled = $enabled;
        $this->autoAcceptForce = $force;

        return $this;
    }

    /**
     * Manually trust a server certificate and add it to the trust store.
     *
     * @param string $certDer DER-encoded certificate bytes.
     * @return void
     */
    public function trustCertificate(string $certDer): void
    {
        if ($this->trustStore === null) {
            return;
        }

        $this->trustStore->trust($certDer);
        $fingerprint = implode(':', str_split(sha1($certDer), 2));
        $this->logger->info('Server certificate manually trusted (fingerprint={fingerprint})', ['fingerprint' => $fingerprint]);
        $this->dispatch(fn () => new ServerCertificateManuallyTrusted($this, $fingerprint));
    }

    /**
     * Remove a server certificate from the trust store.
     *
     * @param string $fingerprint SHA-1 fingerprint (hex, colon-separated or plain hex).
     * @return void
     */
    public function untrustCertificate(string $fingerprint): void
    {
        if ($this->trustStore === null) {
            return;
        }

        $this->trustStore->untrust($fingerprint);
        $this->logger->info('Server certificate removed (fingerprint={fingerprint})', ['fingerprint' => $fingerprint]);
        $this->dispatch(fn () => new ServerCertificateRemoved($this, $fingerprint));
    }

    /**
     * Validate the server certificate against the trust store.
     *
     * @throws UntrustedCertificateException If the certificate is not trusted and auto-accept is disabled.
     */
    private function validateServerCertificate(): void
    {
        if ($this->trustStore === null || $this->trustPolicy === null || $this->serverCertDer === null) {
            return;
        }

        $caCertPem = $this->caCertPath !== null ? @file_get_contents($this->caCertPath) ?: null : null;
        $result = $this->trustStore->validate($this->serverCertDer, $this->trustPolicy, $caCertPem);

        if ($result->trusted) {
            $this->logger->debug('Server certificate trusted (fingerprint={fingerprint})', ['fingerprint' => $result->fingerprint]);
            $this->dispatch(fn () => new ServerCertificateTrusted($this, $result->fingerprint, $result->subject));

            return;
        }

        if ($this->autoAcceptEnabled) {
            if ($this->autoAcceptForce) {
                $this->trustStore->trust($this->serverCertDer);
                $this->logger->info('Server certificate force-accepted (fingerprint={fingerprint})', ['fingerprint' => $result->fingerprint]);
                $this->dispatch(fn () => new ServerCertificateAutoAccepted($this, $result->fingerprint, $result->subject));

                return;
            }

            if (empty($this->trustStore->getTrustedCertificates())) {
                $this->trustStore->trust($this->serverCertDer);
                $this->logger->info('Server certificate auto-accepted (fingerprint={fingerprint})', ['fingerprint' => $result->fingerprint]);
                $this->dispatch(fn () => new ServerCertificateAutoAccepted($this, $result->fingerprint, $result->subject));

                return;
            }
        }

        $this->trustStore->reject($this->serverCertDer);
        $this->logger->warning('Server certificate rejected: {reason} (fingerprint={fingerprint})', [
            'reason' => $result->reason,
            'fingerprint' => $result->fingerprint,
        ]);
        $this->dispatch(fn () => new ServerCertificateRejected($this, $result->fingerprint, $result->reason, $result->subject));

        throw new UntrustedCertificateException(
            $result->fingerprint,
            $this->serverCertDer,
            sprintf(
                "Server certificate not trusted.\n  Fingerprint: %s\n  Subject: %s\n  Reason: %s",
                $result->fingerprint,
                $result->subject ?? 'Unknown',
                $result->reason ?? 'Unknown',
            ),
        );
    }
}
