# Error Handling

## Exception Hierarchy

Everything extends `RuntimeException` through a common base:

```
RuntimeException
  └── OpcUaException
        ├── ConfigurationException
        ├── ConnectionException
        ├── EncodingException
        ├── ProtocolException
        ├── SecurityException
        └── ServiceException
```

All in the `Gianfriaur\OpcuaPhpClient\Exception` namespace.

## Exception Types

### OpcUaException

Base for all library errors. Catch this if you want a single catch-all:

```php
use Gianfriaur\OpcuaPhpClient\Exception\OpcUaException;

try {
    $client->connect('opc.tcp://localhost:4840');
    $value = $client->read(NodeId::numeric(0, 2259));
} catch (OpcUaException $e) {
    echo "OPC UA error: " . $e->getMessage() . "\n";
}
```

### ConfigurationException

Bad configuration:

```php
use Gianfriaur\OpcuaPhpClient\Exception\ConfigurationException;

// Thrown when:
// - Invalid endpoint URL format
// - Certificate file not found or unreadable
// - Private key file not found
// - reconnect() called without prior connect()
```

### ConnectionException

TCP-level problems:

```php
use Gianfriaur\OpcuaPhpClient\Exception\ConnectionException;

// Thrown when:
// - Can't connect to host:port
// - Connection closed by remote
// - Read timeout (configurable via setTimeout(), default: 5s)
// - Failed to send data
// - "Not connected: call connect() first" (state: Disconnected)
// - "Connection lost: call reconnect() or connect() to re-establish" (state: Broken)
```

### EncodingException

Binary encoding/decoding errors:

```php
use Gianfriaur\OpcuaPhpClient\Exception\EncodingException;

// Thrown when:
// - Buffer underflow (not enough data)
// - Invalid GUID format
// - Unknown NodeId encoding byte
// - Unknown variant type
// - DiagnosticInfo encoding not supported
```

### ProtocolException

OPC UA protocol violations:

```php
use Gianfriaur\OpcuaPhpClient\Exception\ProtocolException;

// Thrown when:
// - Server sends ERR during handshake
// - Unexpected message type (expected ACK, got something else)
// - Invalid message size
```

### SecurityException

Crypto problems:

```php
use Gianfriaur\OpcuaPhpClient\Exception\SecurityException;

// Thrown when:
// - Couldn't get server certificate
// - Failed to parse private key
// - Asymmetric signing/encryption/decryption failed
// - Symmetric encryption/decryption failed
// - Certificate read failure
// - PEM decode failure
```

### ServiceException

The OPC UA server returned an error. Carries the status code:

```php
use Gianfriaur\OpcuaPhpClient\Exception\ServiceException;
use Gianfriaur\OpcuaPhpClient\Types\StatusCode;

try {
    $client->read(NodeId::numeric(0, 99999));
} catch (ServiceException $e) {
    echo "Service error: " . $e->getMessage() . "\n";
    echo "Status code: " . sprintf('0x%08X', $e->getStatusCode()) . "\n";
    echo "Status name: " . StatusCode::getName($e->getStatusCode()) . "\n";
}
```

## Recommended Pattern

```php
use Gianfriaur\OpcuaPhpClient\Exception\ConnectionException;
use Gianfriaur\OpcuaPhpClient\Exception\SecurityException;
use Gianfriaur\OpcuaPhpClient\Exception\ServiceException;
use Gianfriaur\OpcuaPhpClient\Exception\OpcUaException;
use Gianfriaur\OpcuaPhpClient\Types\ConnectionState;

try {
    $client->connect('opc.tcp://localhost:4840');
    $value = $client->read(NodeId::numeric(2, 1001));
    $client->disconnect();
} catch (ConnectionException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";

    // check if recovery is possible
    if ($client->getConnectionState() === ConnectionState::Broken) {
        // could try reconnect() or connect() again
    }
} catch (SecurityException $e) {
    echo "Security error: " . $e->getMessage() . "\n";
} catch (ServiceException $e) {
    echo "Server error: " . StatusCode::getName($e->getStatusCode()) . "\n";
} catch (OpcUaException $e) {
    echo "Other OPC UA error: " . $e->getMessage() . "\n";
} finally {
    $client->disconnect();
}
```

> **Tip:** With auto-retry enabled (default: 1 retry after first connect), the client tries to reconnect before throwing. You only need manual recovery if auto-retry is exhausted or disabled.

## Status Codes vs Exceptions

Not every bad status code triggers an exception. The library distinguishes:

- **Exceptions** — connection failures, protocol errors, security failures, server-level errors (ERR messages)
- **Status codes in results** — per-item results from read/write/call that you should check yourself

```php
// This does NOT throw on BadNodeIdUnknown — returns it in the DataValue
$dv = $client->read(NodeId::numeric(0, 99999));
if (StatusCode::isBad($dv->getStatusCode())) {
    echo "Read failed: " . StatusCode::getName($dv->getStatusCode()) . "\n";
}

// Write returns status codes per item
$results = $client->writeMulti([...]);
foreach ($results as $statusCode) {
    if (StatusCode::isBad($statusCode)) {
        // handle failure for this specific item
    }
}
```
