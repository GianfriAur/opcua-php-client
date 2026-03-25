# Error Handling

## Exception Hierarchy

Every exception extends `RuntimeException` through a single base class:

```
RuntimeException
  └── OpcUaException
        ├── ConfigurationException
        ├── ConnectionException
        ├── EncodingException
        ├── InvalidNodeIdException
        ├── ProtocolException
        ├── SecurityException
        │     └── UntrustedCertificateException
        ├── ServiceException
        ├── WriteTypeDetectionException
        └── WriteTypeMismatchException
```

All live in `Gianfriaur\OpcuaPhpClient\Exception`.

## Recommended Try/Catch Pattern

Start here. This covers the most common failure modes in order of likelihood:

```php
use Gianfriaur\OpcuaPhpClient\Exception\ConnectionException;
use Gianfriaur\OpcuaPhpClient\Exception\SecurityException;
use Gianfriaur\OpcuaPhpClient\Exception\ServiceException;
use Gianfriaur\OpcuaPhpClient\Exception\OpcUaException;
use Gianfriaur\OpcuaPhpClient\Types\ConnectionState;
use Gianfriaur\OpcuaPhpClient\Types\StatusCode;

try {
    $client->connect('opc.tcp://localhost:4840');
    $value = $client->read(NodeId::numeric(2, 1001));
    $client->disconnect();

} catch (ConnectionException $e) {
    // TCP-level failure: host unreachable, timeout, connection dropped
    echo "Connection failed: {$e->getMessage()}\n";

    if ($client->getConnectionState() === ConnectionState::Broken) {
        $client->reconnect(); // or connect() again
    }

} catch (SecurityException $e) {
    // Certificate rejected, key mismatch, encryption failure
    echo "Security error: {$e->getMessage()}\n";

} catch (ServiceException $e) {
    // Server returned an OPC UA error status code
    echo "Server error: " . StatusCode::getName($e->getStatusCode()) . "\n";
    echo "Status code: " . sprintf('0x%08X', $e->getStatusCode()) . "\n";

} catch (OpcUaException $e) {
    // Catch-all for anything else (encoding, protocol, config)
    echo "OPC UA error: {$e->getMessage()}\n";

} finally {
    $client->disconnect();
}
```

> **Tip:** With auto-retry enabled (default: 1 retry after first connect), the client attempts reconnection before throwing. You only need manual recovery if auto-retry is exhausted or disabled.

> **Events:** Connection failures dispatch `ConnectionFailed`. Each retry dispatches `RetryAttempt`, and when all retries are exhausted `RetryExhausted` is dispatched. Use these events for monitoring and alerting. See [Events](14-events.md).

## Exception Types

### OpcUaException

Base class for all library exceptions. Catch this when you want a single catch-all:

```php
use Gianfriaur\OpcuaPhpClient\Exception\OpcUaException;

try {
    $client->connect('opc.tcp://localhost:4840');
    $value = $client->read(NodeId::numeric(0, 2259));
} catch (OpcUaException $e) {
    echo "OPC UA error: {$e->getMessage()}\n";
}
```

### ServiceException

The server returned an error. This is the only exception that carries a status code:

```php
use Gianfriaur\OpcuaPhpClient\Exception\ServiceException;
use Gianfriaur\OpcuaPhpClient\Types\StatusCode;

try {
    $client->read(NodeId::numeric(0, 99999));
} catch (ServiceException $e) {
    $code = $e->getStatusCode();
    echo StatusCode::getName($code);        // e.g. "BadNodeIdUnknown"
    echo sprintf('0x%08X', $code);          // e.g. "0x80340000"
}
```

### ConnectionException

TCP-level problems. Thrown when:

- Cannot connect to host/port
- Connection closed by remote
- Read timeout (default: 5s, configurable via `setTimeout()`)
- Failed to send data
- `"Not connected"` -- you called a method before `connect()`
- `"Connection lost"` -- state is `Broken`, call `reconnect()` or `connect()`

### ConfigurationException

Invalid setup. Thrown when:

- Invalid endpoint URL format
- Certificate or private key file not found / unreadable
- `reconnect()` called without prior `connect()`

### SecurityException

Crypto failures. Thrown when:

- Server certificate unavailable or unreadable
- Private key parsing failed
- Asymmetric sign / encrypt / decrypt failed
- Symmetric encrypt / decrypt failed
- PEM decode failure

### EncodingException

Binary encoding/decoding errors. Thrown when:

- Buffer underflow (not enough data)
- Invalid GUID format
- Unknown NodeId encoding byte
- Unknown variant type
- DiagnosticInfo encoding not supported

### InvalidNodeIdException

Malformed node identifiers. Thrown when parsing a string that does not match any valid NodeId format.

### ProtocolException

OPC UA protocol violations. Thrown when:

- Server sends ERR during handshake
- Unexpected message type (expected ACK, got something else)
- Invalid message size

### WriteTypeDetectionException

Thrown when write type auto-detection fails. This happens when:

- Auto-detect is enabled but the node has no readable value (Variant is null)
- Auto-detect is disabled and no explicit `BuiltinType` was provided

```php
use Gianfriaur\OpcuaPhpClient\Exception\WriteTypeDetectionException;

try {
    $client->setAutoDetectWriteType(false);
    $client->write('ns=2;i=1001', 42); // no type provided
} catch (WriteTypeDetectionException $e) {
    echo $e->getMessage();
}
```

### WriteTypeMismatchException

Reserved for type mismatch detection. Carries `$nodeId`, `$expectedType`, and `$givenType`. Currently not thrown by the library — when an explicit type is passed to `write()`, it is used directly without validation. The class exists for use in custom validation logic or future features.

## Status Codes vs Exceptions

Not every bad status code throws an exception. The library draws a clear line:

| Situation | What happens |
|-----------|-------------|
| Connection failure, protocol error, security failure | Exception thrown |
| Server-level error (ERR message) | `ServiceException` thrown |
| Per-item result from read/write/call | Status code in the result -- **you check it** |

```php
// read() does NOT throw on BadNodeIdUnknown -- it returns it in the DataValue
$dv = $client->read(NodeId::numeric(0, 99999));

if (StatusCode::isBad($dv->statusCode)) {
    echo "Read failed: " . StatusCode::getName($dv->statusCode) . "\n";
}
```

```php
// writeMulti() returns status codes per item
$results = $client->writeMulti([...]);

foreach ($results as $statusCode) {
    if (StatusCode::isBad($statusCode)) {
        // This specific write failed
    }
}
```

> **Warning:** Always check `statusCode` on `DataValue` results. A successful `read()` call (no exception) can still contain a bad status code for individual nodes.
