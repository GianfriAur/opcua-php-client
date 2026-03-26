<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Cli\Commands;

use PhpOpcua\Client\Cli\Output\OutputInterface;
use PhpOpcua\Client\ClientBuilder;
use PhpOpcua\Client\OpcUaClientInterface;

/**
 * Removes a trusted server certificate from the trust store.
 */
class TrustRemoveCommand implements CommandInterface
{
    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'trust:remove';
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Remove a trusted server certificate';
    }

    /**
     * {@inheritDoc}
     */
    public function getUsage(): string
    {
        return 'trust:remove <fingerprint> [--trust-store=path]';
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
        if (empty($arguments)) {
            $output->error('Usage: opcua-cli ' . $this->getUsage());

            return 1;
        }

        $trustStore = $client->getTrustStore();
        if ($trustStore === null) {
            $output->error('No trust store configured. Use --trust-store=<path> to specify one.');

            return 1;
        }

        $fingerprint = $arguments[0];
        $client->untrustCertificate($fingerprint);

        $output->writeln('Removed certificate: ' . $fingerprint);

        return 0;
    }
}
