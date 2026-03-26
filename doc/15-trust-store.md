# Trust Store

## Overview

The trust store validates server certificates during secure connections. Instead of accepting any certificate, you can require that server certificates are explicitly trusted before the connection is established.

By default, trust validation is **disabled** (`setTrustPolicy(null)`) — the client behaves exactly as before. Enable it when you need certificate validation for industrial or production deployments.

## Quick Start

```php
use PhpOpcua\Client\ClientBuilder;
use PhpOpcua\Client\TrustStore\FileTrustStore;
use PhpOpcua\Client\TrustStore\TrustPolicy;

$client = ClientBuilder::create()
    ->setTrustStore(new FileTrustStore())            // ~/.opcua/trusted/
    ->setTrustPolicy(TrustPolicy::Fingerprint)
    ->connect('opc.tcp://server:4840');              // throws if not trusted
```

## Trust Policies

| Policy | Validates |
|--------|-----------|
| `TrustPolicy::Fingerprint` | Certificate exists in the trust store |
| `TrustPolicy::FingerprintAndExpiry` | + Certificate is not expired and not yet valid |
| `TrustPolicy::Full` | + CA chain verification (requires CA cert path) |
| `null` | Disabled — accept all certificates (default) |

```php
$builder = ClientBuilder::create();
$builder->setTrustPolicy(TrustPolicy::Full);

// Disable trust validation
$builder->setTrustPolicy(null);
```

## FileTrustStore

File-based implementation. Stores certificates as DER files.

```php
use PhpOpcua\Client\TrustStore\FileTrustStore;

// Default: ~/.opcua/
$store = new FileTrustStore();

// Custom path
$store = new FileTrustStore('/etc/opcua/certs');
```

Directory structure:

```
~/.opcua/
├── trusted/
│   ├── ab12cd34ef56...sha1.der
│   └── 78901234abcd...sha1.der
└── rejected/
    └── deadbeef0123...sha1.der
```

## Auto-Accept (TOFU)

Trust On First Use — automatically accept and save unknown certificates:

```php
$builder = ClientBuilder::create();
$builder->autoAccept(true);                    // accept new certificates
$builder->autoAccept(true, force: true);       // also accept changed certificates
```

**Without `force`:** If a different certificate is already trusted and the server sends a new one, the connection fails. This protects against MITM attacks.

**With `force`:** Changed certificates are accepted and the trust store is updated.

## Manual Trust Management

```php
// Trust a certificate programmatically
$client->trustCertificate($certDer);

// Remove a certificate
$client->untrustCertificate('ab:cd:12:34:...');
```

Both methods dispatch events and log the action.

## UntrustedCertificateException

Thrown when a server certificate is not trusted:

```php
use PhpOpcua\Client\Exception\UntrustedCertificateException;

try {
    $client = ClientBuilder::create()
        ->setTrustPolicy(TrustPolicy::Fingerprint)
        ->connect('opc.tcp://server:4840');
} catch (UntrustedCertificateException $e) {
    echo $e->fingerprint;   // "ab:cd:12:34:..."
    echo $e->certDer;       // raw DER bytes — save or inspect
    echo $e->getMessage();   // human-readable message with fingerprint and reason
}
```

## Events

| Event | When | Log Level |
|-------|------|-----------|
| `ServerCertificateTrusted` | Certificate passes trust store validation | DEBUG |
| `ServerCertificateAutoAccepted` | Certificate auto-accepted via TOFU | INFO |
| `ServerCertificateRejected` | Certificate rejected, saved to rejected/ | WARNING |
| `ServerCertificateManuallyTrusted` | Certificate added via `trustCertificate()` | INFO |
| `ServerCertificateRemoved` | Certificate removed via `untrustCertificate()` | INFO |

All events carry `$client`, `$fingerprint`, and optionally `$subject` and `$reason`.

## CLI Commands

Trust management is also available from the terminal via [`php-opcua/opcua-cli`](https://github.com/php-opcua/opcua-cli):

```bash
opcua-cli trust opc.tcp://server:4840          # download and trust
opcua-cli trust:list                            # list trusted certs
opcua-cli trust:remove ab:cd:12:34:...          # remove a cert
```
