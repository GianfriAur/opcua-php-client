# Connection & Configuration

## Basic Connection

```php
use Gianfriaur\OpcuaPhpClient\Client;

$client = new Client();
$client->connect('opc.tcp://localhost:4840');

// ... operations ...

$client->disconnect();
```

The `connect()` method performs the following steps automatically:
1. Parses the endpoint URL to extract host and port (default: 4840)
2. If security is configured, discovers the server certificate via GetEndpoints
3. Establishes TCP connection
4. Performs the OPC UA Hello/Acknowledge handshake
5. Opens a secure channel (with or without encryption)
6. Creates and activates a session

## Security Configuration

### Security Policy & Mode

```php
use Gianfriaur\OpcuaPhpClient\Security\SecurityPolicy;
use Gianfriaur\OpcuaPhpClient\Security\SecurityMode;

$client = new Client();
$client->setSecurityPolicy(SecurityPolicy::Basic256Sha256);
$client->setSecurityMode(SecurityMode::SignAndEncrypt);
```

**Available Security Policies:**

| Policy | URI |
|--------|-----|
| `SecurityPolicy::None` | `http://opcfoundation.org/UA/SecurityPolicy#None` |
| `SecurityPolicy::Basic128Rsa15` | `http://opcfoundation.org/UA/SecurityPolicy#Basic128Rsa15` |
| `SecurityPolicy::Basic256` | `http://opcfoundation.org/UA/SecurityPolicy#Basic256` |
| `SecurityPolicy::Basic256Sha256` | `http://opcfoundation.org/UA/SecurityPolicy#Basic256Sha256` |
| `SecurityPolicy::Aes128Sha256RsaOaep` | `http://opcfoundation.org/UA/SecurityPolicy#Aes128_Sha256_RsaOaep` |
| `SecurityPolicy::Aes256Sha256RsaPss` | `http://opcfoundation.org/UA/SecurityPolicy#Aes256_Sha256_RsaPss` |

**Available Security Modes:**

| Mode | Value | Description |
|------|-------|-------------|
| `SecurityMode::None` | 1 | No security |
| `SecurityMode::Sign` | 2 | Messages are signed |
| `SecurityMode::SignAndEncrypt` | 3 | Messages are signed and encrypted |

### Client Certificate

Required when using any security policy other than `None`:

```php
$client->setClientCertificate(
    '/path/to/client-cert.pem',   // or .der
    '/path/to/client-key.pem',
    '/path/to/ca-cert.pem'        // optional CA certificate
);
```

Both PEM and DER certificate formats are supported. The library auto-detects the format.

## Authentication

### Anonymous (Default)

No configuration needed. The client uses anonymous authentication by default.

### Username/Password

```php
$client->setUserCredentials('myuser', 'mypassword');
```

When security is active, the password is encrypted with the server's public key before transmission.

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

You can discover available endpoints after connecting:

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

Always call `disconnect()` when done. It:
1. Sends CloseSession request
2. Sends CloseSecureChannel request
3. Closes the TCP socket
4. Clears all internal state

```php
$client->disconnect();
```
