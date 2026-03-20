# Connection & Configuration

## Basic Connection

```php
use Gianfriaur\OpcuaPhpClient\Client;

$client = new Client();
$client->connect('opc.tcp://localhost:4840');

// ... do stuff ...

$client->disconnect();
```

When you call `connect()`, behind the scenes it:
1. Parses the endpoint URL (host + port, default 4840)
2. If security is configured, discovers the server certificate via GetEndpoints
3. Opens the TCP connection
4. Runs the OPC UA Hello/Acknowledge handshake
5. Opens a secure channel (encrypted or not, depending on config)
6. Creates and activates a session
7. Reads server operation limits (`MaxNodesPerRead`, `MaxNodesPerWrite`) for auto-batching (skipped if you called `setBatchSize(0)`)

## Timeout

Default timeout is **5 seconds** for both TCP connection and socket I/O. Change it with `setTimeout()`:

```php
$client = new Client();
$client->setTimeout(10.0); // 10 seconds
$client->connect('opc.tcp://localhost:4840');
```

The timeout applies to the initial connection and all subsequent operations (handshake, secure channel, browse, read, write, etc.). If exceeded, you get a `ConnectionException` with "Read timeout".

> **Tip:** Bump the timeout for high-latency networks or slow PLCs. For fast local connections, you can lower it.

## Connection State

The client tracks where it stands via `ConnectionState`:

```php
use Gianfriaur\OpcuaPhpClient\Types\ConnectionState;

$client = new Client();
$client->getConnectionState(); // ConnectionState::Disconnected

$client->connect('opc.tcp://localhost:4840');
$client->getConnectionState(); // ConnectionState::Connected
$client->isConnected();        // true

$client->disconnect();
$client->getConnectionState(); // ConnectionState::Disconnected
```

| State | Meaning |
|-------|---------|
| `ConnectionState::Disconnected` | Never connected, or cleanly disconnected |
| `ConnectionState::Connected` | Up and running |
| `ConnectionState::Broken` | Connection was lost (timeout, remote close, etc.) |

The state also affects error messages when you try to do something on a non-connected client:
- `Disconnected` → `"Not connected: call connect() first"`
- `Broken` → `"Connection lost: call reconnect() or connect() to re-establish"`

## Reconnect

If the connection drops, `reconnect()` does a full disconnect/connect cycle using the last endpoint URL:

```php
$client->connect('opc.tcp://localhost:4840');

// ... connection drops ...

$client->reconnect(); // re-establishes to opc.tcp://localhost:4840
```

`reconnect()` throws `ConfigurationException` if you never called `connect()`. After an explicit `disconnect()`, the endpoint URL is cleared — use `connect()` again instead.

## Auto-Retry

The client can automatically reconnect and retry when a `ConnectionException` hits during an operation:

```php
$client = new Client();
$client->setAutoRetry(3); // retry up to 3 times on connection failure
$client->connect('opc.tcp://localhost:4840');

// If read() fails because the connection broke, the client will:
// 1. Mark state as Broken
// 2. Call reconnect()
// 3. Retry the operation
// ...up to 3 times before giving up
$value = $client->read(NodeId::numeric(0, 2259));
```

**Defaults:**
- **0 retries** if never connected or after `disconnect()`
- **1 retry** once you've connected at least once (even if it failed)

To disable:

```php
$client->setAutoRetry(0);
```

> **Note:** Auto-retry only kicks in for `ConnectionException` during operations (read, write, browse, etc.). The initial `connect()` itself doesn't retry. After an explicit `disconnect()`, there's no endpoint to reconnect to, so retry is off.

## Security Configuration

### Security Policy & Mode

```php
use Gianfriaur\OpcuaPhpClient\Security\SecurityPolicy;
use Gianfriaur\OpcuaPhpClient\Security\SecurityMode;

$client = new Client();
$client->setSecurityPolicy(SecurityPolicy::Basic256Sha256);
$client->setSecurityMode(SecurityMode::SignAndEncrypt);
```

**Security Policies:**

| Policy | URI |
|--------|-----|
| `SecurityPolicy::None` | `http://opcfoundation.org/UA/SecurityPolicy#None` |
| `SecurityPolicy::Basic128Rsa15` | `http://opcfoundation.org/UA/SecurityPolicy#Basic128Rsa15` |
| `SecurityPolicy::Basic256` | `http://opcfoundation.org/UA/SecurityPolicy#Basic256` |
| `SecurityPolicy::Basic256Sha256` | `http://opcfoundation.org/UA/SecurityPolicy#Basic256Sha256` |
| `SecurityPolicy::Aes128Sha256RsaOaep` | `http://opcfoundation.org/UA/SecurityPolicy#Aes128_Sha256_RsaOaep` |
| `SecurityPolicy::Aes256Sha256RsaPss` | `http://opcfoundation.org/UA/SecurityPolicy#Aes256_Sha256_RsaPss` |

**Security Modes:**

| Mode | Value | What it does |
|------|-------|--------------|
| `SecurityMode::None` | 1 | No security |
| `SecurityMode::Sign` | 2 | Messages are signed |
| `SecurityMode::SignAndEncrypt` | 3 | Messages are signed and encrypted |

### Client Certificate

Needed for any security policy other than `None`:

```php
$client->setClientCertificate(
    '/path/to/client-cert.pem',   // or .der
    '/path/to/client-key.pem',
    '/path/to/ca-cert.pem'        // optional CA certificate
);
```

Both PEM and DER are supported — the library auto-detects the format.

## Authentication

### Anonymous (Default)

Nothing to configure — anonymous is the default.

### Username/Password

```php
$client->setUserCredentials('myuser', 'mypassword');
```

When security is active, the password gets encrypted with the server's public key before going over the wire.

### X.509 Certificate

```php
$client->setUserCertificate(
    '/path/to/user-cert.pem',
    '/path/to/user-key.pem'
);
```

## Full Secure Connection Example

```php
$client = new Client();

$client->setTimeout(10.0); // optional

$client->setSecurityPolicy(SecurityPolicy::Basic256Sha256);
$client->setSecurityMode(SecurityMode::SignAndEncrypt);

$client->setClientCertificate(
    '/certs/client.pem',
    '/certs/client.key',
    '/certs/ca.pem'
);

$client->setUserCredentials('operator', 'secret123');

$client->connect('opc.tcp://192.168.1.100:4840/UA/Server');

// ... secure operations ...

$client->disconnect();
```

## Endpoint Discovery

You can discover what the server offers after connecting:

```php
$client = new Client();
$client->connect('opc.tcp://localhost:4840');

$endpoints = $client->getEndpoints('opc.tcp://localhost:4840');
foreach ($endpoints as $ep) {
    echo "URL: " . $ep->getEndpointUrl() . "\n";
    echo "Security: " . $ep->getSecurityPolicyUri() . "\n";
    echo "Mode: " . $ep->getSecurityMode() . "\n";

    foreach ($ep->getUserIdentityTokens() as $token) {
        echo "  Auth: " . $token->getPolicyId()
            . " (type=" . $token->getTokenType() . ")\n";
    }
}
```

**Token types:**
- `0` = Anonymous
- `1` = Username/Password
- `2` = X.509 Certificate

## Disconnection

Always call `disconnect()` when you're done. It:
1. Sends CloseSession
2. Sends CloseSecureChannel
3. Closes the TCP socket
4. Clears all internal state (including the last endpoint URL)
5. Sets state to `Disconnected`

```php
$client->disconnect();
```

After `disconnect()`, auto-retry is off and `reconnect()` won't work. Call `connect()` with a URL to start fresh.
