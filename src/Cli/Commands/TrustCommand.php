<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Cli\Commands;

use Gianfriaur\OpcuaPhpClient\Cli\Output\OutputInterface;
use Gianfriaur\OpcuaPhpClient\ClientBuilder;
use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Downloads and trusts a server certificate.
 */
class TrustCommand implements CommandInterface
{
    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'trust';
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Download and trust a server certificate';
    }

    /**
     * {@inheritDoc}
     */
    public function getUsage(): string
    {
        return 'trust <endpoint> [--trust-store=path]';
    }

    /**
     * {@inheritDoc}
     */
    public function requiresConnection(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(OpcUaClientInterface|ClientBuilder $client, array $arguments, array $options, OutputInterface $output): int
    {
        if (empty($arguments)) {
            $output->error('Usage: opcua-cli ' . $this->getUsage());

            return 1;
        }

        $endpointUrl = $arguments[0];
        $endpoints = $client->getEndpoints($endpointUrl);

        $certDer = null;
        foreach ($endpoints as $ep) {
            if ($ep->serverCertificate !== null) {
                $certDer = $ep->serverCertificate;

                break;
            }
        }

        if ($certDer === null) {
            $output->error('No server certificate found at ' . $endpointUrl);

            return 1;
        }

        $trustStore = $client->getTrustStore();
        if ($trustStore === null) {
            $output->error('No trust store configured. Use --trust-store=<path> to specify one.');

            return 1;
        }

        $client->trustCertificate($certDer);

        $fingerprint = implode(':', str_split(sha1($certDer), 2));
        $pem = "-----BEGIN CERTIFICATE-----\n" . chunk_split(base64_encode($certDer), 64) . "-----END CERTIFICATE-----\n";
        $parsed = @openssl_x509_parse($pem);
        $subject = $parsed['subject']['CN'] ?? 'Unknown';
        $notAfter = isset($parsed['validTo_time_t']) ? date('c', (int) $parsed['validTo_time_t']) : 'N/A';

        $output->data([
            'Status' => 'Trusted',
            'Fingerprint' => $fingerprint,
            'Subject' => $subject,
            'Expires' => $notAfter,
        ]);

        return 0;
    }
}
