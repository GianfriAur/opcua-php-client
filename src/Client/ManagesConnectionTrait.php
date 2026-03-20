<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Client;

use Closure;
use Gianfriaur\OpcuaPhpClient\Exception\ConfigurationException;
use Gianfriaur\OpcuaPhpClient\Exception\ConnectionException;
use Gianfriaur\OpcuaPhpClient\Exception\OpcUaException;
use Gianfriaur\OpcuaPhpClient\Security\SecurityMode;
use Gianfriaur\OpcuaPhpClient\Security\SecurityPolicy;
use Gianfriaur\OpcuaPhpClient\Types\ConnectionState;

/**
 * Provides connection lifecycle management including connect, reconnect, disconnect, and automatic retry logic.
 */
trait ManagesConnectionTrait
{
    /**
     * Connect to an OPC UA server endpoint.
     *
     * @param string $endpointUrl The OPC UA endpoint URL (e.g. "opc.tcp://host:4840").
     * @return void
     *
     * @throws ConfigurationException If the endpoint URL is invalid.
     * @throws ConnectionException If the TCP connection or handshake fails.
     *
     * @see self::reconnect()
     * @see self::disconnect()
     */
    public function connect(string $endpointUrl): void
    {
        $parsed = parse_url($endpointUrl);
        if ($parsed === false || !isset($parsed['host'])) {
            throw new ConfigurationException("Invalid endpoint URL: {$endpointUrl}");
        }

        $host = $parsed['host'];
        $port = $parsed['port'] ?? 4840;

        $isSecure = $this->securityPolicy !== SecurityPolicy::None
            && $this->securityMode !== SecurityMode::None;

        if ($isSecure && $this->serverCertDer === null) {
            $this->discoverServerCertificate($host, $port, $endpointUrl);
        }

        try {
            $this->transport->connect($host, $port, $this->getTimeout());

            $this->doHandshake($endpointUrl);

            $this->openSecureChannel();

            $this->createAndActivateSession($endpointUrl);
        } catch (ConnectionException $e) {
            $this->connectionState = ConnectionState::Broken;
            $this->lastEndpointUrl = $endpointUrl;
            throw $e;
        }

        $this->lastEndpointUrl = $endpointUrl;
        $this->connectionState = ConnectionState::Connected;

        $this->discoverServerOperationLimits();
    }

    /**
     * Reconnect to the previously connected endpoint.
     *
     * @return void
     *
     * @throws ConfigurationException If no previous endpoint exists (connect() was never called).
     * @throws ConnectionException If the reconnection attempt fails.
     *
     * @see self::connect()
     */
    public function reconnect(): void
    {
        if ($this->lastEndpointUrl === null) {
            throw new ConfigurationException('Cannot reconnect: no previous connection endpoint. Call connect() first.');
        }

        $this->transport->close();
        $this->resetConnectionState();

        $this->connect($this->lastEndpointUrl);
    }

    /**
     * Gracefully disconnect from the server, closing the session and secure channel.
     *
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->session !== null && $this->authenticationToken !== null) {
            try {
                $this->closeSession();
            } catch (OpcUaException) {
            }
        }

        if ($this->secureChannelId !== 0) {
            try {
                $this->closeSecureChannel();
            } catch (OpcUaException) {
            }
        }

        $this->transport->close();

        $this->resetConnectionState();
        $this->lastEndpointUrl = null;
        $this->connectionState = ConnectionState::Disconnected;
    }

    /**
     * Check whether the client is currently connected.
     *
     * @return bool True if the connection state is Connected, false otherwise.
     */
    public function isConnected(): bool
    {
        return $this->connectionState === ConnectionState::Connected;
    }

    /**
     * Get the current connection state.
     *
     * @return ConnectionState
     *
     * @see ConnectionState
     */
    public function getConnectionState(): ConnectionState
    {
        return $this->connectionState;
    }

    /**
     * @throws ConnectionException
     */
    private function ensureConnected(): void
    {
        if ($this->connectionState === ConnectionState::Connected) {
            return;
        }

        throw match ($this->connectionState) {
            ConnectionState::Disconnected => new ConnectionException('Not connected: call connect() first'),
            ConnectionState::Broken => new ConnectionException('Connection lost: call reconnect() or connect() to re-establish'),
            default => throw new ConnectionException('No explicit exception for state: ' . $this->connectionState->name),
        };
    }

    /**
     * @template T
     * @param Closure(): T $operation
     * @return T
     */
    private function executeWithRetry(Closure $operation): mixed
    {
        $maxRetries = $this->getAutoRetry();

        for ($attempt = 0; ; $attempt++) {
            try {
                return $operation();
            } catch (ConnectionException $e) {
                $this->connectionState = ConnectionState::Broken;

                if ($attempt >= $maxRetries || $this->lastEndpointUrl === null) {
                    throw $e;
                }

                $this->reconnect();
            }
        }
    }

    private function resetConnectionState(): void
    {
        $this->session = null;
        $this->browseService = null;
        $this->readService = null;
        $this->writeService = null;
        $this->callService = null;
        $this->getEndpointsService = null;
        $this->subscriptionService = null;
        $this->monitoredItemService = null;
        $this->publishService = null;
        $this->historyReadService = null;
        $this->translateBrowsePathService = null;
        $this->authenticationToken = null;
        $this->secureChannelId = 0;
        $this->secureChannel = null;
        $this->serverNonce = null;
        $this->resetBatchingState();
    }
}
