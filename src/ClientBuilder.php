<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient;

use Gianfriaur\OpcuaPhpClient\ClientBuilder\ManagesAutoRetryTrait;
use Gianfriaur\OpcuaPhpClient\ClientBuilder\ManagesBatchingTrait;
use Gianfriaur\OpcuaPhpClient\ClientBuilder\ManagesBrowseDepthTrait;
use Gianfriaur\OpcuaPhpClient\ClientBuilder\ManagesCacheTrait;
use Gianfriaur\OpcuaPhpClient\ClientBuilder\ManagesEventDispatcherTrait;
use Gianfriaur\OpcuaPhpClient\ClientBuilder\ManagesReadWriteConfigTrait;
use Gianfriaur\OpcuaPhpClient\ClientBuilder\ManagesTimeoutTrait;
use Gianfriaur\OpcuaPhpClient\ClientBuilder\ManagesTrustStoreTrait;
use Gianfriaur\OpcuaPhpClient\Event\NullEventDispatcher;
use Gianfriaur\OpcuaPhpClient\Repository\ExtensionObjectRepository;
use Gianfriaur\OpcuaPhpClient\Security\SecurityMode;
use Gianfriaur\OpcuaPhpClient\Security\SecurityPolicy;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * OPC UA client builder. Configures security, credentials, cache, and other options before connecting.
 *
 * All configuration methods return `self` for fluent chaining. Call {@see connect()} to
 * establish the connection and obtain a {@see Client} instance with operation methods.
 *
 * The builder is reusable: calling `connect()` multiple times creates independent
 * connected clients sharing the same configuration snapshot.
 *
 * @implements ClientBuilderInterface
 *
 * @see ClientBuilderInterface
 * @see Client
 */
class ClientBuilder implements ClientBuilderInterface
{
    use ManagesTimeoutTrait;
    use ManagesAutoRetryTrait;
    use ManagesBatchingTrait;
    use ManagesCacheTrait;
    use ManagesBrowseDepthTrait;
    use ManagesEventDispatcherTrait;
    use ManagesTrustStoreTrait;
    use ManagesReadWriteConfigTrait;

    private SecurityPolicy $securityPolicy = SecurityPolicy::None;

    private SecurityMode $securityMode = SecurityMode::None;

    private ?string $username = null;

    private ?string $password = null;

    private ?string $clientCertPath = null;

    private ?string $clientKeyPath = null;

    private ?string $caCertPath = null;

    private ?string $userCertPath = null;

    private ?string $userKeyPath = null;

    private ExtensionObjectRepository $extensionObjectRepository;

    private LoggerInterface $logger;

    /**
     * Create a new client builder instance.
     *
     * @param ?ExtensionObjectRepository $extensionObjectRepository Optional custom repository for extension object decoding.
     * @param ?LoggerInterface $logger Optional PSR-3 logger for connection events, retries, and errors.
     * @return static
     */
    public static function create(?ExtensionObjectRepository $extensionObjectRepository = null, ?LoggerInterface $logger = null): static
    {
        return new static($extensionObjectRepository, $logger);
    }

    /**
     * @param ?ExtensionObjectRepository $extensionObjectRepository Optional custom repository for extension object decoding.
     * @param ?LoggerInterface $logger Optional PSR-3 logger for connection events, retries, and errors.
     */
    public function __construct(?ExtensionObjectRepository $extensionObjectRepository = null, ?LoggerInterface $logger = null)
    {
        $this->extensionObjectRepository = $extensionObjectRepository ?? new ExtensionObjectRepository();
        $this->logger = $logger ?? new NullLogger();
        $this->eventDispatcher = new NullEventDispatcher();
    }

    /**
     * Set the PSR-3 logger for connection events, retries, and errors.
     *
     * @param LoggerInterface $logger
     * @return self
     */
    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Get the configured logger.
     *
     * @return LoggerInterface
     */
    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Return the extension object repository used for custom type decoding.
     *
     * @return ExtensionObjectRepository
     *
     * @see ExtensionObjectRepository
     */
    public function getExtensionObjectRepository(): ExtensionObjectRepository
    {
        return $this->extensionObjectRepository;
    }

    /**
     * Set the security policy for the connection.
     *
     * @param SecurityPolicy $policy The security policy to use.
     * @return self
     *
     * @see SecurityPolicy
     */
    public function setSecurityPolicy(SecurityPolicy $policy): self
    {
        $this->securityPolicy = $policy;

        return $this;
    }

    /**
     * Set the message security mode for the connection.
     *
     * @param SecurityMode $mode The security mode to use.
     * @return self
     *
     * @see SecurityMode
     */
    public function setSecurityMode(SecurityMode $mode): self
    {
        $this->securityMode = $mode;

        return $this;
    }

    /**
     * Set username/password credentials for session authentication.
     *
     * @param string $username The username.
     * @param string $password The password.
     * @return self
     */
    public function setUserCredentials(string $username, string $password): self
    {
        $this->username = $username;
        $this->password = $password;

        return $this;
    }

    /**
     * Set the client application certificate and private key for channel-level security.
     *
     * @param string $certPath Path to the client certificate file (DER or PEM).
     * @param string $keyPath Path to the client private key file.
     * @param ?string $caCertPath Optional path to the CA certificate for chain validation.
     * @return self
     */
    public function setClientCertificate(string $certPath, string $keyPath, ?string $caCertPath = null): self
    {
        $this->clientCertPath = $certPath;
        $this->clientKeyPath = $keyPath;
        $this->caCertPath = $caCertPath;

        return $this;
    }

    /**
     * Set the user certificate and private key for X509 identity token authentication.
     *
     * @param string $certPath Path to the user certificate file.
     * @param string $keyPath Path to the user private key file.
     * @return self
     */
    public function setUserCertificate(string $certPath, string $keyPath): self
    {
        $this->userCertPath = $certPath;
        $this->userKeyPath = $keyPath;

        return $this;
    }

    /**
     * Connect to an OPC UA server endpoint.
     *
     * Creates a new {@see Client} instance with a snapshot of the current configuration,
     * establishes the TCP connection, handshake, secure channel, and session.
     *
     * @param string $endpointUrl The OPC UA endpoint URL (e.g. "opc.tcp://host:4840").
     * @return Client
     *
     * @throws Exception\ConfigurationException If the endpoint URL is invalid.
     * @throws Exception\ConnectionException If the TCP connection or handshake fails.
     * @throws Exception\ServiceException If a protocol-level error occurs during session creation.
     */
    public function connect(string $endpointUrl): Client
    {
        $this->ensureCacheInitialized();

        return new Client(
            endpointUrl: $endpointUrl,
            securityPolicy: $this->securityPolicy,
            securityMode: $this->securityMode,
            clientCertPath: $this->clientCertPath,
            clientKeyPath: $this->clientKeyPath,
            caCertPath: $this->caCertPath,
            username: $this->username,
            password: $this->password,
            userCertPath: $this->userCertPath,
            userKeyPath: $this->userKeyPath,
            logger: $this->logger,
            eventDispatcher: $this->eventDispatcher,
            trustStore: $this->trustStore,
            trustPolicy: $this->trustPolicy,
            autoAcceptEnabled: $this->autoAcceptEnabled,
            autoAcceptForce: $this->autoAcceptForce,
            cache: $this->cache,
            cacheInitialized: $this->cacheInitialized,
            timeout: $this->timeout,
            autoRetry: $this->autoRetry,
            batchSize: $this->batchSize,
            defaultBrowseMaxDepth: $this->defaultBrowseMaxDepth,
            autoDetectWriteType: $this->autoDetectWriteType,
            readMetadataCache: $this->readMetadataCache,
            extensionObjectRepository: $this->extensionObjectRepository,
            enumMappings: $this->enumMappings,
        );
    }
}
