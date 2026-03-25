<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Cli\Commands;

use Gianfriaur\OpcuaPhpClient\Cli\Output\OutputInterface;
use Gianfriaur\OpcuaPhpClient\ClientBuilder;
use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Lists all trusted server certificates in the trust store.
 */
class TrustListCommand implements CommandInterface
{
    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'trust:list';
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'List trusted server certificates';
    }

    /**
     * {@inheritDoc}
     */
    public function getUsage(): string
    {
        return 'trust:list [--trust-store=path]';
    }

    /**
     * {@inheritDoc}
     */
    public function requiresConnection(): bool
    {
        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(OpcUaClientInterface|ClientBuilder $client, array $arguments, array $options, OutputInterface $output): int
    {
        $trustStore = $client->getTrustStore();
        if ($trustStore === null) {
            $output->error('No trust store configured. Use --trust-store=<path> to specify one.');

            return 1;
        }

        $certs = $trustStore->getTrustedCertificates();

        if (empty($certs)) {
            $output->writeln('No trusted certificates.');

            return 0;
        }

        $rows = [];
        foreach ($certs as $cert) {
            $rows[] = [
                'Fingerprint' => $cert['fingerprint'],
                'Subject' => $cert['subject'] ?? 'Unknown',
                'Expires' => $cert['notAfter']?->format('c') ?? 'N/A',
            ];
        }

        $output->table($rows);

        return 0;
    }
}
