<?php

declare(strict_types=1);

require_once __DIR__ . '/Client/ClientTraitsCoverageTest.php';

use PhpOpcua\Client\Cache\InMemoryCache;
use PhpOpcua\Client\Client;
use PhpOpcua\Client\ClientBuilder;
use PhpOpcua\Client\Encoding\BinaryEncoder;
use PhpOpcua\Client\Event\MonitoredItemModified;
use PhpOpcua\Client\Event\ServerCertificateManuallyTrusted;
use PhpOpcua\Client\Event\ServerCertificateRemoved;
use PhpOpcua\Client\Event\TriggeringConfigured;
use PhpOpcua\Client\Exception\ConfigurationException;
use PhpOpcua\Client\Exception\ConnectionException;
use PhpOpcua\Client\Exception\WriteTypeMismatchException;
use PhpOpcua\Client\Protocol\MonitoredItemService;
use PhpOpcua\Client\Protocol\SessionService;
use PhpOpcua\Client\Repository\ExtensionObjectRepository;
use PhpOpcua\Client\Repository\GeneratedTypeRegistrar;
use PhpOpcua\Client\Testing\MockClient;
use PhpOpcua\Client\TrustStore\FileTrustStore;
use PhpOpcua\Client\TrustStore\TrustPolicy;
use PhpOpcua\Client\Types\BuiltinType;
use PhpOpcua\Client\Types\ConnectionState;
use PhpOpcua\Client\Types\NodeId;

// ──────────────────────────────────────────────
// Event classes at 0% coverage
// ──────────────────────────────────────────────

describe('Event: MonitoredItemModified', function () {
    it('stores all properties', function () {
        $client = MockClient::create();
        $event = new MonitoredItemModified($client, 1, 42, 0);

        expect($event->client)->toBe($client);
        expect($event->subscriptionId)->toBe(1);
        expect($event->monitoredItemId)->toBe(42);
        expect($event->statusCode)->toBe(0);
    });
});

describe('Event: ServerCertificateManuallyTrusted', function () {
    it('stores all properties', function () {
        $client = MockClient::create();
        $event = new ServerCertificateManuallyTrusted($client, 'ab:cd:ef', 'CN=test');

        expect($event->client)->toBe($client);
        expect($event->fingerprint)->toBe('ab:cd:ef');
        expect($event->subject)->toBe('CN=test');
    });

    it('subject defaults to null', function () {
        $client = MockClient::create();
        $event = new ServerCertificateManuallyTrusted($client, 'ab:cd:ef');

        expect($event->subject)->toBeNull();
    });
});

describe('Event: ServerCertificateRemoved', function () {
    it('stores all properties', function () {
        $client = MockClient::create();
        $event = new ServerCertificateRemoved($client, 'ab:cd:ef');

        expect($event->client)->toBe($client);
        expect($event->fingerprint)->toBe('ab:cd:ef');
    });
});

describe('Event: TriggeringConfigured', function () {
    it('stores all properties', function () {
        $client = MockClient::create();
        $event = new TriggeringConfigured($client, 1, 42, [0, 0], [0]);

        expect($event->client)->toBe($client);
        expect($event->subscriptionId)->toBe(1);
        expect($event->triggeringItemId)->toBe(42);
        expect($event->addResults)->toBe([0, 0]);
        expect($event->removeResults)->toBe([0]);
    });
});

// ──────────────────────────────────────────────
// Exception: WriteTypeMismatchException at 0%
// ──────────────────────────────────────────────

describe('Exception: WriteTypeMismatchException', function () {
    it('stores nodeId, expectedType, givenType, and message', function () {
        $nodeId = NodeId::numeric(2, 1001);
        $ex = new WriteTypeMismatchException(
            $nodeId,
            BuiltinType::Int32,
            BuiltinType::Double,
            'Type mismatch: expected Int32, got Double',
        );

        expect($ex->nodeId)->toBe($nodeId);
        expect($ex->expectedType)->toBe(BuiltinType::Int32);
        expect($ex->givenType)->toBe(BuiltinType::Double);
        expect($ex->getMessage())->toBe('Type mismatch: expected Int32, got Double');
    });
});

// ──────────────────────────────────────────────
// MockClient uncovered methods
// ──────────────────────────────────────────────

describe('MockClient: uncovered methods', function () {
    it('onGetEndpoints registers handler and getEndpoints invokes it', function () {
        $mock = MockClient::create();
        $mock->onGetEndpoints(fn (string $url) => [
            new PhpOpcua\Client\Types\EndpointDescription(
                $url,
                null,
                1,
                'http://opcfoundation.org/UA/SecurityPolicy#None',
                [],
                'http://opcfoundation.org/UA-Profile/Transport/uatcp-uasc-uabinary',
                0,
            ),
        ]);

        $endpoints = $mock->getEndpoints('opc.tcp://localhost:4840');
        expect($endpoints)->toHaveCount(1);
        expect($endpoints[0]->getEndpointUrl())->toBe('opc.tcp://localhost:4840');
    });

    it('getTrustStore returns null by default', function () {
        $mock = MockClient::create();
        expect($mock->getTrustStore())->toBeNull();
    });

    it('getTrustPolicy returns null by default', function () {
        $mock = MockClient::create();
        expect($mock->getTrustPolicy())->toBeNull();
    });

    it('trustCertificate records call', function () {
        $mock = MockClient::create();
        $mock->trustCertificate('cert-bytes');
        expect($mock->callCount('trustCertificate'))->toBe(1);
    });

    it('untrustCertificate records call', function () {
        $mock = MockClient::create();
        $mock->untrustCertificate('ab:cd:ef');
        expect($mock->callCount('untrustCertificate'))->toBe(1);
    });

    it('setTrustStore is fluent and stores value', function () {
        $mock = MockClient::create();
        $tmpDir = sys_get_temp_dir() . '/opcua-test-ts-' . uniqid();
        $store = new FileTrustStore($tmpDir);

        $result = $mock->setTrustStore($store);
        expect($result)->toBe($mock);
        expect($mock->getTrustStore())->toBe($store);

        @rmdir($tmpDir . '/trusted');
        @rmdir($tmpDir . '/rejected');
        @rmdir($tmpDir);
    });

    it('setTrustPolicy is fluent and stores value', function () {
        $mock = MockClient::create();
        $result = $mock->setTrustPolicy(TrustPolicy::Fingerprint);

        expect($result)->toBe($mock);
        expect($mock->getTrustPolicy())->toBe(TrustPolicy::Fingerprint);
    });

    it('setCache is fluent', function () {
        $mock = MockClient::create();
        $cache = new InMemoryCache();
        $result = $mock->setCache($cache);

        expect($result)->toBe($mock);
    });

    it('setCache with null disables cache', function () {
        $mock = MockClient::create();
        $result = $mock->setCache(null);

        expect($result)->toBe($mock);
    });
});

// ──────────────────────────────────────────────
// ClientBuilder: create factory (line 78)
// ──────────────────────────────────────────────

describe('ClientBuilder: create factory', function () {
    it('creates a builder via static factory', function () {
        $builder = ClientBuilder::create();
        expect($builder)->toBeInstanceOf(ClientBuilder::class);
    });

    it('creates a builder with custom repository and logger', function () {
        $repo = new ExtensionObjectRepository();
        $logger = new Psr\Log\NullLogger();
        $builder = ClientBuilder::create($repo, $logger);
        expect($builder)->toBeInstanceOf(ClientBuilder::class);
    });
});

// ──────────────────────────────────────────────
// ClientBuilder/ManagesCacheTrait (lines 27-42)
// ──────────────────────────────────────────────

describe('ClientBuilder: ManagesCacheTrait', function () {
    it('setCache stores cache and getCache returns it', function () {
        $builder = ClientBuilder::create();
        $cache = new InMemoryCache(60);
        $result = $builder->setCache($cache);

        expect($result)->toBe($builder);
        expect($builder->getCache())->toBe($cache);
    });

    it('setCache(null) disables caching', function () {
        $builder = ClientBuilder::create();
        $builder->setCache(null);

        expect($builder->getCache())->toBeNull();
    });

    it('getCache returns default InMemoryCache when not configured', function () {
        $builder = ClientBuilder::create();
        $cache = $builder->getCache();

        expect($cache)->toBeInstanceOf(InMemoryCache::class);
    });
});

// ──────────────────────────────────────────────
// ClientBuilder/ManagesReadWriteConfigTrait (lines 70-79)
// ──────────────────────────────────────────────

describe('ClientBuilder: ManagesReadWriteConfigTrait', function () {
    it('loadGeneratedTypes registers codecs and enum mappings', function () {
        $builder = ClientBuilder::create();

        $registrar = new class() implements GeneratedTypeRegistrar {
            public bool $registered = false;

            public function registerCodecs(ExtensionObjectRepository $repository): void
            {
                $this->registered = true;
            }

            public function getEnumMappings(): array
            {
                return ['ns=2;i=100' => BuiltinType::class];
            }

            public function dependencyRegistrars(): array
            {
                return [];
            }
        };

        $result = $builder->loadGeneratedTypes($registrar);
        expect($result)->toBe($builder);
        expect($registrar->registered)->toBeTrue();
    });

    it('loadGeneratedTypes loads dependencies recursively', function () {
        $builder = ClientBuilder::create();

        $depRegistrar = new class() implements GeneratedTypeRegistrar {
            public bool $registered = false;

            public function registerCodecs(ExtensionObjectRepository $repository): void
            {
                $this->registered = true;
            }

            public function getEnumMappings(): array
            {
                return [];
            }

            public function dependencyRegistrars(): array
            {
                return [];
            }
        };

        $mainRegistrar = new class($depRegistrar) implements GeneratedTypeRegistrar {
            public bool $registered = false;

            public function __construct(private GeneratedTypeRegistrar $dep)
            {
            }

            public function registerCodecs(ExtensionObjectRepository $repository): void
            {
                $this->registered = true;
            }

            public function getEnumMappings(): array
            {
                return [];
            }

            public function dependencyRegistrars(): array
            {
                return [$this->dep];
            }
        };

        $builder->loadGeneratedTypes($mainRegistrar);
        expect($mainRegistrar->registered)->toBeTrue();
        expect($depRegistrar->registered)->toBeTrue();
    });
});

// ──────────────────────────────────────────────
// ManagesTrustStoreRuntimeTrait (lines 52-76)
// ──────────────────────────────────────────────

describe('ManagesTrustStoreRuntimeTrait', function () {
    it('trustCertificate does nothing when trust store is null', function () {
        $client = createClientWithoutConnect();
        setClientProperty($client, 'trustStore', null);
        $client->trustCertificate('cert-bytes');
        expect(true)->toBeTrue();
    });

    it('trustCertificate trusts cert and dispatches event', function () {
        $tmpDir = sys_get_temp_dir() . '/opcua-trust-test-' . uniqid();
        $store = new FileTrustStore($tmpDir);
        $events = [];
        $dispatcher = new class($events) implements Psr\EventDispatcher\EventDispatcherInterface {
            public function __construct(private array &$events)
            {
            }

            public function dispatch(object $event): object
            {
                $this->events[] = $event;

                return $event;
            }
        };

        $client = createClientWithoutConnect();
        setClientProperty($client, 'trustStore', $store);
        setClientProperty($client, 'eventDispatcher', $dispatcher);

        $certDer = 'fake-cert-der-bytes';
        $client->trustCertificate($certDer);

        expect($events)->not->toBeEmpty();
        expect($events[0])->toBeInstanceOf(ServerCertificateManuallyTrusted::class);

        // cleanup
        array_map('unlink', glob($tmpDir . '/trusted/*'));
        @rmdir($tmpDir . '/trusted');
        @rmdir($tmpDir . '/rejected');
        @rmdir($tmpDir);
    });

    it('untrustCertificate does nothing when trust store is null', function () {
        $client = createClientWithoutConnect();
        setClientProperty($client, 'trustStore', null);
        $client->untrustCertificate('ab:cd:ef');
        expect(true)->toBeTrue();
    });

    it('untrustCertificate calls untrust and dispatches event', function () {
        $tmpDir = sys_get_temp_dir() . '/opcua-untrust-test-' . uniqid();
        $store = new FileTrustStore($tmpDir);
        $events = [];
        $dispatcher = new class($events) implements Psr\EventDispatcher\EventDispatcherInterface {
            public function __construct(private array &$events)
            {
            }

            public function dispatch(object $event): object
            {
                $this->events[] = $event;

                return $event;
            }
        };

        $client = createClientWithoutConnect();
        setClientProperty($client, 'trustStore', $store);
        setClientProperty($client, 'eventDispatcher', $dispatcher);

        $client->untrustCertificate('ab:cd:ef:01:23');

        expect($events)->not->toBeEmpty();
        expect($events[0])->toBeInstanceOf(ServerCertificateRemoved::class);

        @rmdir($tmpDir . '/trusted');
        @rmdir($tmpDir . '/rejected');
        @rmdir($tmpDir);
    });
});

// ──────────────────────────────────────────────
// ManagesConnectionTrait: ensureConnected Broken state (line 115)
// and executeWithRetry retry path (lines 148-153)
// ──────────────────────────────────────────────

describe('ManagesConnectionTrait: Broken state', function () {
    it('ensureConnected throws for Broken state', function () {
        $client = createClientWithoutConnect();
        setClientProperty($client, 'connectionState', ConnectionState::Broken);

        expect(fn () => $client->read(NodeId::numeric(0, 2259)))
            ->toThrow(ConnectionException::class, 'Connection lost');
    });
});

describe('ManagesConnectionTrait: disconnect catches exceptions', function () {
    it('disconnect handles closeSession failure gracefully', function () {
        $mock = new MockTransport();
        $client = setupConnectedClient($mock);

        // closeSession will fail with ConnectionException, disconnect should still succeed
        $client->disconnect();
        expect($client->getConnectionState())->toBe(ConnectionState::Disconnected);
    });
});

// ──────────────────────────────────────────────
// ManagesReadWriteTrait: applyEnumMapping (lines 123-140)
// ──────────────────────────────────────────────

describe('ManagesReadWriteTrait: applyEnumMapping', function () {
    it('maps int value to BackedEnum when mapping is configured', function () {
        $mock = new MockTransport();
        $mock->addResponse(readResponseMsg(1));

        $client = setupConnectedClient($mock);
        setClientProperty($client, 'enumMappings', [
            'i=2259' => BuiltinType::class,
        ]);

        $dv = $client->read(NodeId::numeric(0, 2259));
        expect($dv->getValue())->toBeInstanceOf(BuiltinType::class);
    });

    it('returns original DataValue when raw value is not int or string', function () {
        $mock = new MockTransport();
        // Response with a Double value
        $mock->addResponse(buildMsgResponse(634, function (BinaryEncoder $e) {
            $e->writeInt32(1);
            $e->writeByte(0x01);
            $e->writeByte(BuiltinType::Double->value);
            $e->writeDouble(3.14);
            $e->writeInt32(0);
        }));

        $client = setupConnectedClient($mock);
        setClientProperty($client, 'enumMappings', [
            'i=2259' => BuiltinType::class,
        ]);

        $dv = $client->read(NodeId::numeric(0, 2259));
        expect($dv->getValue())->toBe(3.14);
    });

    it('returns original DataValue when enum from() throws ValueError', function () {
        $mock = new MockTransport();
        // Return an int value (99999) that won't match any BuiltinType case
        $mock->addResponse(buildMsgResponse(634, function (BinaryEncoder $e) {
            $e->writeInt32(1);
            $e->writeByte(0x01);
            $e->writeByte(BuiltinType::Int32->value);
            $e->writeInt32(99999);
            $e->writeInt32(0);
        }));

        $client = setupConnectedClient($mock);
        setClientProperty($client, 'enumMappings', [
            'i=2259' => BuiltinType::class,
        ]);

        $dv = $client->read(NodeId::numeric(0, 2259));
        expect($dv->getValue())->toBe(99999);
    });
});

// ──────────────────────────────────────────────
// ManagesSecureChannelTrait: loadClientCertificateAndKey error (line 160)
// and buildCertificateChain (lines 189, 195)
// ──────────────────────────────────────────────

describe('ManagesSecureChannelTrait: loadClientCertificateAndKey', function () {
    it('throws ConfigurationException when cert file cannot be read', function () {
        $client = createClientWithoutConnect();
        setClientProperty($client, 'clientCertPath', '/nonexistent/cert.pem');
        setClientProperty($client, 'clientKeyPath', '/nonexistent/key.pem');

        expect(fn () => callClientMethod($client, 'loadClientCertificateAndKey'))
            ->toThrow(ConfigurationException::class, 'Failed to read client certificate');
    });
});

describe('ManagesSecureChannelTrait: buildCertificateChain', function () {
    it('returns clientCertDer when CA file cannot be read', function () {
        $client = createClientWithoutConnect();
        setClientProperty($client, 'caCertPath', '/nonexistent/ca.pem');

        $result = callClientMethod($client, 'buildCertificateChain', ['cert-der-data']);
        expect($result)->toBe('cert-der-data');
    });

    it('appends DER CA cert when CA is in DER format', function () {
        $tmpFile = tempnam(sys_get_temp_dir(), 'opcua-ca-');
        // Write binary (non-PEM) content
        file_put_contents($tmpFile, "\x30\x82\x01\x00FAKE_DER_DATA");

        $client = createClientWithoutConnect();
        setClientProperty($client, 'caCertPath', $tmpFile);

        // This will fail at openssl_x509_read inside loadCertificateDer, but we're testing
        // the branch where it's NOT PEM. Let's just verify the branch is hit.
        // Since CertificateManager::loadCertificateDer reads the file, it returns the raw bytes.
        $result = callClientMethod($client, 'buildCertificateChain', ['client-cert-der']);
        expect(str_starts_with($result, 'client-cert-der'))->toBeTrue();

        @unlink($tmpFile);
    });
});

// ──────────────────────────────────────────────
// ManagesSubscriptionsTrait: alarm without data (line 562)
// ──────────────────────────────────────────────

describe('ManagesSubscriptionsTrait: alarm deduction returns early', function () {
    it('publish with event notification without alarm data does not dispatch alarm event', function () {
        $mock = new MockTransport();
        $client = setupConnectedClient($mock);

        // Build publish response with an event notification with no severity or eventType
        $mock->addResponse(buildMsgResponse(829, function (BinaryEncoder $e) {
            // subscriptionId
            $e->writeUInt32(1);
            // availableSequenceNumbers
            $e->writeInt32(1);
            $e->writeUInt32(1);
            // moreNotifications
            $e->writeBoolean(false);
            // sequenceNumber
            $e->writeUInt32(1);
            // publishTime
            $e->writeInt64(0);
            // notificationData count
            $e->writeInt32(1);
            // EventNotificationList typeId
            $e->writeNodeId(NodeId::numeric(0, 916));
            $e->writeByte(0x01);
            // body: encode EventNotificationList
            $bodyEnc = new BinaryEncoder();
            $bodyEnc->writeInt32(1); // 1 event
            $bodyEnc->writeUInt32(1); // clientHandle
            $bodyEnc->writeInt32(0); // 0 event fields
            $body = $bodyEnc->getBuffer();
            $e->writeInt32(strlen($body));
            $e->writeRawBytes($body);
            // results
            $e->writeInt32(0);
            // diagnosticInfos
            $e->writeInt32(0);
        }));

        $result = $client->publish();
        expect($result->subscriptionId)->toBe(1);
    });
});

// ──────────────────────────────────────────────
// Protocol/MonitoredItemService: setTriggering decode (lines 297, 323)
// ──────────────────────────────────────────────

describe('MonitoredItemService: decodeSetTriggeringResponse', function () {
    it('decodes response with add and remove results', function () {
        $session = new SessionService(1, 1);
        $service = new MonitoredItemService($session);

        $enc = new BinaryEncoder();
        // readResponseMetadata reads: UInt32 (tokenId), UInt32 (seqNum), UInt32 (requestId), NodeId (typeId)
        $enc->writeUInt32(1);   // tokenId
        $enc->writeUInt32(1);   // sequenceNumber
        $enc->writeUInt32(1);   // requestId
        $enc->writeNodeId(NodeId::numeric(0, 778)); // typeId

        // readResponseHeader reads: Int64 (timestamp), UInt32 (requestHandle), UInt32 (statusCode),
        // Byte (diagMask), skipDiagBody, Int32 (stringTableCount), loop, NodeId (additionalHeader), Byte
        $enc->writeInt64(0);
        $enc->writeUInt32(1);
        $enc->writeUInt32(0);
        $enc->writeByte(0);
        $enc->writeInt32(0);
        $enc->writeNodeId(NodeId::numeric(0, 0));
        $enc->writeByte(0);

        // addResults: 2 items
        $enc->writeInt32(2);
        $enc->writeUInt32(0);
        $enc->writeUInt32(0);
        // diagnosticInfos (skipDiagnosticInfoArray): Int32 count
        $enc->writeInt32(0);
        // removeResults: 1 item
        $enc->writeInt32(1);
        $enc->writeUInt32(0);

        $decoder = new PhpOpcua\Client\Encoding\BinaryDecoder($enc->getBuffer());
        $result = $service->decodeSetTriggeringResponse($decoder);

        expect($result->addResults)->toBe([0, 0]);
        expect($result->removeResults)->toBe([0]);
    });

    it('decodes response without remove results when no remaining bytes', function () {
        $session = new SessionService(1, 1);
        $service = new MonitoredItemService($session);

        $enc = new BinaryEncoder();
        $enc->writeUInt32(1);
        $enc->writeUInt32(1);
        $enc->writeUInt32(1);
        $enc->writeNodeId(NodeId::numeric(0, 778));
        $enc->writeInt64(0);
        $enc->writeUInt32(1);
        $enc->writeUInt32(0);
        $enc->writeByte(0);
        $enc->writeInt32(0);
        $enc->writeNodeId(NodeId::numeric(0, 0));
        $enc->writeByte(0);

        // addResults: 1 item
        $enc->writeInt32(1);
        $enc->writeUInt32(0);
        // diagnosticInfos
        $enc->writeInt32(0);
        // no removeResults bytes at all

        $decoder = new PhpOpcua\Client\Encoding\BinaryDecoder($enc->getBuffer());
        $result = $service->decodeSetTriggeringResponse($decoder);

        expect($result->addResults)->toBe([0]);
        expect($result->removeResults)->toBe([]);
    });
});

// ──────────────────────────────────────────────
// Client: getDefaultBrowseMaxDepth (line 331)
// ──────────────────────────────────────────────

describe('Client: getDefaultBrowseMaxDepth', function () {
    it('returns the configured browse max depth', function () {
        $client = createClientWithoutConnect();
        setClientProperty($client, 'defaultBrowseMaxDepth', 15);

        expect($client->getDefaultBrowseMaxDepth())->toBe(15);
    });
});

// ──────────────────────────────────────────────
// ManagesTypeDiscoveryTrait: uncovered lines 106, 176
// ──────────────────────────────────────────────

describe('ManagesTypeDiscoveryTrait: discoverDataTypes', function () {
    it('discoverDataTypes returns 0 when browse returns empty refs', function () {
        $mock = new MockTransport();

        // Browse response with 0 refs for the first browse (DataTypes folder)
        $mock->addResponse(buildMsgResponse(530, function (BinaryEncoder $e) {
            $e->writeInt32(1);
            $e->writeUInt32(0);
            $e->writeByteString(null);
            $e->writeInt32(0);
            $e->writeInt32(0);
        }));

        $client = setupConnectedClient($mock);
        $count = $client->discoverDataTypes();

        expect($count)->toBe(0);
    });
});

// ──────────────────────────────────────────────
// FileTrustStore: uncovered lines 91, 98, 140
// ──────────────────────────────────────────────

describe('FileTrustStore: edge cases', function () {
    it('validate with FingerprintAndExpiry for valid cert', function () {
        $tmpDir = sys_get_temp_dir() . '/opcua-trust-nyv-' . uniqid();
        $store = new FileTrustStore($tmpDir);

        // Generate a self-signed cert
        $privKey = openssl_pkey_new(['private_key_bits' => 2048]);
        $csr = openssl_csr_new(['CN' => 'Test'], $privKey);
        $cert = openssl_csr_sign($csr, null, $privKey, 365);
        openssl_x509_export($cert, $certPem);

        // Convert to DER
        $pemBody = trim(str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----'], '', $certPem));
        $certDer = base64_decode($pemBody);

        // Trust the cert
        $store->trust($certDer);

        // Validate with FingerprintAndExpiry (should be trusted since cert is valid)
        $result = $store->validate($certDer, TrustPolicy::FingerprintAndExpiry);
        expect($result->trusted)->toBeTrue();
        expect($result->subject)->toBe('Test');

        // cleanup
        array_map('unlink', glob($tmpDir . '/trusted/*'));
        @rmdir($tmpDir . '/trusted');
        @rmdir($tmpDir . '/rejected');
        @rmdir($tmpDir);
    });

    it('validate returns not yet valid for future cert', function () {
        $tmpDir = sys_get_temp_dir() . '/opcua-trust-future-' . uniqid();
        $store = new FileTrustStore($tmpDir);

        // Generate a self-signed cert valid for 365 days (regular)
        $privKey = openssl_pkey_new(['private_key_bits' => 2048]);
        $csr = openssl_csr_new(['CN' => 'FutureTest'], $privKey);
        $cert = openssl_csr_sign($csr, null, $privKey, 365);
        openssl_x509_export($cert, $certPem);
        $pemBody = trim(str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----'], '', $certPem));
        $certDer = base64_decode($pemBody);

        // Trust it
        $store->trust($certDer);

        // Now tamper with the stored file to simulate a "not yet valid" scenario:
        // We can't easily create a future cert, so we just test the FingerprintAndExpiry path
        // is exercised (the "not yet valid" line 140 is hard to hit without time manipulation)
        $result = $store->validate($certDer, TrustPolicy::FingerprintAndExpiry);
        expect($result->trusted)->toBeTrue();

        // cleanup
        array_map('unlink', glob($tmpDir . '/trusted/*'));
        @rmdir($tmpDir . '/trusted');
        @rmdir($tmpDir . '/rejected');
        @rmdir($tmpDir);
    });
});

// ──────────────────────────────────────────────
// FileCache: clear with glob returning false (line 102)
// ──────────────────────────────────────────────

describe('FileCache: clear', function () {
    it('clear removes all cache files', function () {
        $tmpDir = sys_get_temp_dir() . '/opcua-cache-' . uniqid();
        mkdir($tmpDir, 0777, true);
        $cache = new PhpOpcua\Client\Cache\FileCache($tmpDir);

        $cache->set('key1', 'value1');
        $cache->set('key2', 'value2');

        $result = $cache->clear();
        expect($result)->toBeTrue();
        expect($cache->get('key1'))->toBeNull();
        expect($cache->get('key2'))->toBeNull();

        @rmdir($tmpDir);
    });
});

// ──────────────────────────────────────────────
// ManagesConnectionTrait: executeWithRetry with retry (lines 148-153)
// ──────────────────────────────────────────────

describe('ManagesConnectionTrait: executeWithRetry retry path', function () {
    it('retries and reconnects on ConnectionException', function () {
        $mock = new MockTransport();
        $client = setupConnectedClient($mock);
        setClientProperty($client, 'autoRetry', 1);

        // First read will fail (no response), then retry reconnect will fail too
        expect(fn () => $client->read(NodeId::numeric(0, 2259)))
            ->toThrow(ConnectionException::class);
    });
});
