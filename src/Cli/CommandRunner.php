<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Cli;

use PhpOpcua\Client\Cli\Output\OutputInterface;
use PhpOpcua\Client\ClientBuilder;
use PhpOpcua\Client\Security\SecurityMode;
use PhpOpcua\Client\Security\SecurityPolicy;
use PhpOpcua\Client\TrustStore\FileTrustStore;
use PhpOpcua\Client\TrustStore\TrustPolicy;
use Psr\Log\LoggerInterface;

/**
 * Configures and manages the OPC UA Client lifecycle for CLI commands.
 */
class CommandRunner
{
    /**
     * @param array<string, string|bool> $options
     * @param OutputInterface $output
     * @return ClientBuilder
     */
    public function createClientBuilder(array $options, OutputInterface $output): ClientBuilder
    {
        $logger = $this->createLogger($options, $output);
        $client = new ClientBuilder(logger: $logger);

        if (isset($options['timeout'])) {
            $client->setTimeout((float) $options['timeout']);
        }

        $this->configureSecurity($client, $options);
        $this->configureAuthentication($client, $options);
        $this->configureTrustStore($client, $options);

        return $client;
    }

    /**
     * @param ClientBuilder $client
     * @param array<string, string|bool> $options
     */
    private function configureSecurity(ClientBuilder $client, array $options): void
    {
        if (isset($options['security-policy'])) {
            $policy = SecurityPolicy::tryFrom('http://opcfoundation.org/UA/SecurityPolicy#' . $options['security-policy']);
            if ($policy === null) {
                $policy = SecurityPolicy::from((string) $options['security-policy']);
            }
            $client->setSecurityPolicy($policy);
        }

        if (isset($options['security-mode'])) {
            $mode = match ((string) $options['security-mode']) {
                'None', '1' => SecurityMode::None,
                'Sign', '2' => SecurityMode::Sign,
                'SignAndEncrypt', '3' => SecurityMode::SignAndEncrypt,
                default => SecurityMode::None,
            };
            $client->setSecurityMode($mode);
        }

        $certPath = $options['cert'] ?? null;
        $keyPath = $options['key'] ?? null;
        $caPath = $options['ca'] ?? null;

        if (is_string($certPath) && is_string($keyPath)) {
            $client->setClientCertificate($certPath, $keyPath, is_string($caPath) ? $caPath : null);
        }
    }

    /**
     * @param ClientBuilder $client
     * @param array<string, string|bool> $options
     */
    private function configureAuthentication(ClientBuilder $client, array $options): void
    {
        $username = $options['username'] ?? null;
        $password = $options['password'] ?? null;

        if (is_string($username) && is_string($password)) {
            $client->setUserCredentials($username, $password);
        }
    }

    /**
     * @param ClientBuilder $client
     * @param array<string, string|bool> $options
     */
    private function configureTrustStore(ClientBuilder $client, array $options): void
    {
        if (isset($options['no-trust-policy'])) {
            $client->setTrustPolicy(null);

            return;
        }

        $storePath = isset($options['trust-store']) && is_string($options['trust-store'])
            ? $options['trust-store']
            : null;

        if ($storePath !== null || isset($options['trust-policy'])) {
            $client->setTrustStore(new FileTrustStore($storePath));
        }

        if (isset($options['trust-policy']) && is_string($options['trust-policy'])) {
            $policy = TrustPolicy::tryFrom($options['trust-policy']);
            if ($policy !== null) {
                $client->setTrustPolicy($policy);
            }
        }
    }

    /**
     * @param array<string, string|bool> $options
     * @param OutputInterface $output
     * @return LoggerInterface
     */
    private function createLogger(array $options, OutputInterface $output): LoggerInterface
    {
        if (isset($options['debug-file']) && is_string($options['debug-file'])) {
            return new StreamLogger(fopen($options['debug-file'], 'a'));
        }

        if (isset($options['debug-stderr']) && $options['debug-stderr'] === true) {
            return new StreamLogger(STDERR);
        }

        if (isset($options['debug']) && $options['debug'] === true) {
            return new StreamLogger(STDOUT);
        }

        return new \Psr\Log\NullLogger();
    }
}
