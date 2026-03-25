# Architecture

## Project Structure

```
src/
├── ClientBuilder.php                    # Builder / entry point
├── ClientBuilderInterface.php           # Builder interface
├── Client.php                           # Connected client (operations)
├── OpcUaClientInterface.php             # Public API interface
│
├── ClientBuilder/                       # Builder traits (configuration)
│   ├── ManagesAutoRetryTrait.php        # Auto-retry configuration
│   ├── ManagesBatchingTrait.php         # Batch size configuration
│   ├── ManagesBrowseDepthTrait.php      # Recursive browse depth config
│   ├── ManagesCacheTrait.php            # PSR-16 cache configuration
│   ├── ManagesEventDispatcherTrait.php  # PSR-14 event dispatcher config
│   ├── ManagesReadWriteConfigTrait.php  # Read/write config (auto-detect, metadata cache)
│   ├── ManagesTimeoutTrait.php          # Timeout configuration
│   └── ManagesTrustStoreTrait.php       # Trust store configuration
│
├── Client/                              # Connected client traits (operations, runtime)
│   ├── ManagesBatchingRuntimeTrait.php  # Runtime batch splitting
│   ├── ManagesBrowseTrait.php           # Browse operations
│   ├── ManagesCacheRuntimeTrait.php     # Runtime cache operations
│   ├── ManagesConnectionTrait.php       # Connect / disconnect / reconnect
│   ├── ManagesEventDispatchTrait.php    # Runtime event dispatching
│   ├── ManagesHandshakeTrait.php        # HEL/ACK handshake
│   ├── ManagesHistoryTrait.php          # History read operations
│   ├── ManagesReadWriteTrait.php        # Read / write operations
│   ├── ManagesSecureChannelTrait.php    # Secure channel lifecycle
│   ├── ManagesSessionTrait.php          # Session create / activate
│   ├── ManagesSubscriptionsTrait.php    # Subscriptions and monitored items
│   ├── ManagesTranslateBrowsePathTrait.php # Browse path translation
│   ├── ManagesTrustStoreRuntimeTrait.php # Runtime trust store validation
│   └── ManagesTypeDiscoveryTrait.php    # Automatic DataType discovery
│
├── Transport/
│   └── TcpTransport.php                # TCP socket I/O
│
├── Encoding/
│   ├── BinaryEncoder.php               # Serialization (write)
│   ├── BinaryDecoder.php               # Deserialization (read)
│   ├── ExtensionObjectCodec.php        # Interface for custom type codecs
│   ├── DynamicCodec.php               # Auto-generated codec from StructureDefinition
│   ├── DataTypeMapping.php            # Maps DataType NodeIds to BuiltinTypes
│   └── StructureDefinitionParser.php  # Parses DataTypeDefinition attributes
│
├── Protocol/
│   ├── AbstractProtocolService.php     # Shared encode/decode base class
│   ├── ServiceTypeId.php              # Named constants for OPC UA service NodeIds
│   ├── MessageHeader.php               # OPC UA message framing
│   ├── HelloMessage.php                # HEL message
│   ├── AcknowledgeMessage.php          # ACK message
│   ├── SecureChannelRequest.php        # OPN request
│   ├── SecureChannelResponse.php       # OPN response
│   ├── SessionService.php             # CreateSession / ActivateSession
│   ├── BrowseService.php              # Browse / BrowseNext
│   ├── ReadService.php                # Read
│   ├── WriteService.php               # Write
│   ├── CallService.php                # Call (method invocation)
│   ├── GetEndpointsService.php        # GetEndpoints
│   ├── SubscriptionService.php        # Create / Modify / Delete Subscription
│   ├── MonitoredItemService.php       # Create / Delete MonitoredItems
│   ├── PublishService.php             # Publish (notifications)
│   ├── HistoryReadService.php         # HistoryRead (raw / processed / attime)
│   └── TranslateBrowsePathService.php # TranslateBrowsePathsToNodeIds
│
├── Security/
│   ├── SecurityPolicy.php             # Security policy enum + algorithm config
│   ├── SecurityMode.php               # Security mode enum
│   ├── SecureChannel.php              # Secure channel lifecycle
│   ├── MessageSecurity.php            # Cryptographic operations
│   └── CertificateManager.php        # Certificate loading & utilities
│
├── Types/
│   ├── BuiltinType.php                # OPC UA type enum
│   ├── NodeClass.php                  # Node class enum
│   ├── NodeId.php                     # Node identifier
│   ├── Variant.php                    # Typed value container
│   ├── DataValue.php                  # Value + status + timestamps
│   ├── QualifiedName.php              # Namespace-qualified name
│   ├── LocalizedText.php             # Locale-aware text
│   ├── ReferenceDescription.php      # Browse result item
│   ├── EndpointDescription.php       # Server endpoint info
│   ├── UserTokenPolicy.php           # Authentication policy
│   ├── StatusCode.php                # Status code constants & helpers
│   ├── AttributeId.php               # Attribute ID constants
│   ├── ConnectionState.php           # Connection state enum
│   ├── BrowseDirection.php           # Browse direction enum
│   ├── BrowseNode.php                # Recursive browse tree node DTO
│   ├── BrowseResultSet.php           # Browse with continuation result DTO
│   ├── BrowsePathResult.php          # Translate browse path result DTO
│   ├── BrowsePathTarget.php          # Single resolved browse path target DTO
│   ├── CallResult.php                # Method call result DTO
│   ├── SubscriptionResult.php        # Create subscription result DTO
│   ├── MonitoredItemResult.php       # Create monitored item result DTO
│   ├── PublishResult.php             # Publish response result DTO
│   ├── ExtensionObject.php           # Typed ExtensionObject DTO (raw or decoded)
│   ├── StructureField.php            # Field descriptor for structure definitions
│   └── StructureDefinition.php       # Structure layout for dynamic codecs
│
├── Builder/                            # Fluent builders for multi-operations
│   ├── ReadMultiBuilder.php           # Builder for readMulti()
│   ├── WriteMultiBuilder.php          # Builder for writeMulti()
│   ├── MonitoredItemsBuilder.php      # Builder for createMonitoredItems()
│   └── TranslateBrowsePathsBuilder.php # Builder for translateBrowsePaths()
│
├── Event/
│   ├── NullEventDispatcher.php        # No-op PSR-14 dispatcher (default)
│   ├── Client*.php                    # Connection lifecycle events (6)
│   ├── Session*.php                   # Session events (3)
│   ├── Subscription*.php              # Subscription events (4)
│   ├── MonitoredItem*.php             # Monitored item events (2)
│   ├── DataChangeReceived.php         # Data change notification event
│   ├── EventNotificationReceived.php  # Event notification event
│   ├── PublishResponseReceived.php    # Publish response event
│   ├── SubscriptionKeepAlive.php      # Keep-alive event
│   ├── Alarm*.php                     # Alarm events (8)
│   ├── NodeValue*.php                 # Read/Write events (3)
│   ├── NodeBrowsed.php                # Browse event
│   ├── SecureChannel*.php             # Secure channel events (2)
│   ├── DataTypesDiscovered.php        # Type discovery event
│   ├── Cache*.php                     # Cache hit/miss events (2)
│   ├── Retry*.php                     # Retry events (2)
│   └── ServerCertificate*.php         # Trust store events (5)
│
├── Cli/
│   ├── Application.php                # CLI entry point and routing
│   ├── ArgvParser.php                 # Zero-dep argument parser
│   ├── CommandRunner.php              # Client configuration from CLI options
│   ├── StreamLogger.php               # PSR-3 logger for streams
│   ├── NodeSetParser.php              # NodeSet2.xml parser
│   ├── CodeGenerator.php             # PHP code generator from parsed NodeSet
│   ├── NodeSetXmlBuilder.php         # Build NodeSet2.xml from discovered nodes
│   ├── Commands/
│   │   ├── BrowseCommand.php          # Browse with tree rendering
│   │   ├── ReadCommand.php            # Read node values/attributes
│   │   ├── WriteCommand.php           # Write values to nodes
│   │   ├── EndpointsCommand.php       # Discover endpoints
│   │   ├── WatchCommand.php           # Real-time value watching
│   │   ├── GenerateNodesetCommand.php # Generate PHP from NodeSet2.xml
│   │   ├── DumpNodesetCommand.php     # Export server address space to NodeSet2.xml
│   │   ├── TrustCommand.php           # Trust a server certificate
│   │   ├── TrustListCommand.php       # List trusted certificates
│   │   └── TrustRemoveCommand.php     # Remove a trusted certificate
│   └── Output/
│       ├── ConsoleOutput.php          # ANSI colors, tree chars
│       └── JsonOutput.php             # JSON output
│
├── TrustStore/
│   ├── TrustStoreInterface.php        # Trust store contract
│   ├── FileTrustStore.php             # File-based implementation (~/.opcua/)
│   ├── TrustPolicy.php               # Validation policy enum
│   └── TrustResult.php               # Validation result DTO
│
├── Cache/
│   ├── InMemoryCache.php              # PSR-16 in-memory cache
│   └── FileCache.php                  # PSR-16 file-based cache
│
├── Repository/
│   └── ExtensionObjectRepository.php  # Per-client codec registry
│
├── Testing/
│   └── MockClient.php                # In-memory test double (no TCP)
│
└── Exception/
    ├── OpcUaException.php             # Base exception
    ├── ConfigurationException.php     # Config errors
    ├── ConnectionException.php        # TCP errors
    ├── EncodingException.php          # Binary codec errors
    ├── InvalidNodeIdException.php     # Malformed NodeId errors
    ├── ProtocolException.php          # Protocol violations
    ├── SecurityException.php          # Crypto errors
    ├── UntrustedCertificateException.php # Untrusted server cert (extends SecurityException)
    └── ServiceException.php           # Server errors (with status code)
```

## Layers

```
┌─────────────────────────────────┐
│       ClientBuilder             │  Configuration & entry point
│  (+ ClientBuilder/*Trait.php)   │  Config traits (cache, events, etc.)
├─────────────────────────────────┤
│           Client                │  Connected client (operations)
│     (+ Client/*Trait.php)       │  Runtime traits (browse, read, etc.)
├─────────────────────────────────┤
│     Protocol/*Service           │  OPC UA service encoding/decoding
├──────────────┬──────────────────┤
│ BinaryEncoder│  BinaryDecoder   │  Binary serialization
├──────────────┴──────────────────┤
│       SecureChannel             │  Message-level security
├─────────────────────────────────┤
│    MessageSecurity              │  Cryptographic operations
│    CertificateManager           │  Certificate handling
├─────────────────────────────────┤
│       TcpTransport              │  TCP socket I/O
└─────────────────────────────────┘
```

`ClientBuilder` is the entry point for configuration. Calling `connect()` returns a `Client` for operations. Each layer only talks to the one directly below it.

## Dependencies

The library has three Composer dependencies (all interface-only, zero runtime code):

- **`psr/log`** — PSR-3 logger interface. The client accepts any `Psr\Log\LoggerInterface` implementation (Monolog, Laravel, etc.) and defaults to `NullLogger` when none is provided.
- **`psr/simple-cache`** — PSR-16 cache interface. The client uses `CacheInterface` for browse result caching. Ships with `InMemoryCache` (default) and `FileCache`. Any PSR-16 compatible driver (Laravel Cache, Symfony Cache, etc.) can be plugged in.
- **`psr/event-dispatcher`** — PSR-14 event dispatcher interface. The client dispatches 47 granular events at lifecycle points. Defaults to `NullEventDispatcher` (zero overhead). Any PSR-14 compatible dispatcher (Laravel, Symfony, etc.) can be injected.

The only PHP extension required is `ext-openssl`.

## Service Pattern

All protocol services follow the same structure:

1. Each wraps a `SessionService` instance
2. Separate `encode` and `decode` methods per operation
3. Both secure and non-secure code paths
4. Inner body construction is factored out for reuse

```
encodeFooRequest()
  ├── (no security) → write headers + body → wrapInMessage()
  └── (security)    → write body → secureChannel->buildMessage()

decodeFooResponse()
  └── read headers → readResponseHeader() → parse result fields
```

Adding a new OPC UA service means writing one class with `encode*Request()` and `decode*Response()` methods. The security, framing, and transport layers handle everything else.

## Message Format

### Non-Secure

```
┌──────────┬─────────────┬──────────┬───────────┬─────────────┐
│ MSG/F    │ MessageSize │ ChannelId│ TokenId   │ Sequence    │
│ (3+1 B)  │ (4 B)       │ (4 B)    │ (4 B)     │ Num + ReqId │
├──────────┴─────────────┴──────────┴───────────┴─────────────┤
│                     Service Body                             │
│  (TypeId + RequestHeader + Service-specific fields)          │
└──────────────────────────────────────────────────────────────┘
```

### Secure

```
┌──────────┬─────────────┬──────────┐
│ MSG/F    │ MessageSize │ ChannelId│
├──────────┴─────────────┴──────────┤
│ TokenId │ SequenceNum │ RequestId │
├─────────┴─────────────┴───────────┤
│          Encrypted Body           │  ← AES-CBC
│   (TypeId + Headers + Fields)     │
│   + Padding + PaddingByte         │
├───────────────────────────────────┤
│          HMAC Signature           │  ← HMAC-SHA1/SHA256
└───────────────────────────────────┘
```

## Binary Encoding Notes

The library implements OPC UA Binary encoding (Part 6 of the spec):

- **Little-endian** byte order for all integers
- **Length-prefixed strings** -- `Int32` length + UTF-8 bytes, `-1` for null
- **NodeId compact encoding** -- TwoByte / FourByte / Numeric / String / Guid / Opaque, chosen automatically based on namespace and identifier
- **Variant** -- type byte with optional array dimension flag
- **DataValue** -- bitmask header indicating which optional fields are present (value, status, timestamps)
- **DateTime** -- 100-nanosecond intervals since 1601-01-01 UTC
