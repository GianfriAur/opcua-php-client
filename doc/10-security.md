# Security

## Overview

The library supports the full OPC UA security stack including:

- Asymmetric encryption (RSA) for the initial secure channel
- Symmetric encryption (AES-CBC) for message-level security
- Digital signatures (RSA-SHA1, RSA-SHA256, RSA-PSS-SHA256)
- Key derivation (P_SHA1, P_SHA256)
- X.509 certificate management

## Security Policies

Each policy defines the algorithms used for encryption and signing:

| Policy | Asymmetric Sign | Asymmetric Encrypt | Symmetric Sign | Symmetric Encrypt | Min Key |
|--------|----------------|-------------------|---------------|-------------------|---------|
| None | - | - | - | - | - |
| Basic128Rsa15 | RSA-SHA1 | RSA-PKCS1-v1_5 | HMAC-SHA1 | AES-128-CBC | 1024 bit |
| Basic256 | RSA-SHA1 | RSA-OAEP | HMAC-SHA1 | AES-256-CBC | 1024 bit |
| Basic256Sha256 | RSA-SHA256 | RSA-OAEP | HMAC-SHA256 | AES-256-CBC | 2048 bit |
| Aes128Sha256RsaOaep | RSA-SHA256 | RSA-OAEP | HMAC-SHA256 | AES-128-CBC | 2048 bit |
| Aes256Sha256RsaPss | RSA-PSS-SHA256 | RSA-OAEP-SHA256 | HMAC-SHA256 | AES-256-CBC | 2048 bit |

## Certificate Setup

### Generating Test Certificates

```bash
# Generate CA key and certificate
openssl genpkey -algorithm RSA -out ca.key -pkeyopt rsa_keygen_bits:2048
openssl req -x509 -new -key ca.key -days 365 -out ca.pem \
  -subj "/CN=Test CA"

# Generate client key and certificate
openssl genpkey -algorithm RSA -out client.key -pkeyopt rsa_keygen_bits:2048
openssl req -new -key client.key -out client.csr \
  -subj "/CN=OPC UA Client" \
  -addext "subjectAltName=URI:urn:opcua-php-client:client"
openssl x509 -req -in client.csr -CA ca.pem -CAkey ca.key \
  -CAcreateserial -days 365 -out client.pem \
  -copy_extensions copy

# Convert to DER if needed
openssl x509 -in client.pem -outform der -out client.der
```

### Configuring the Client

```php
use Gianfriaur\OpcuaPhpClient\Client;
use Gianfriaur\OpcuaPhpClient\Security\SecurityPolicy;
use Gianfriaur\OpcuaPhpClient\Security\SecurityMode;

$client = new Client();

// Set security
$client->setSecurityPolicy(SecurityPolicy::Basic256Sha256);
$client->setSecurityMode(SecurityMode::SignAndEncrypt);

// Set client certificate (PEM or DER accepted)
$client->setClientCertificate(
    '/path/to/client.pem',
    '/path/to/client.key',
    '/path/to/ca.pem'       // optional: appended to certificate chain
);

$client->connect('opc.tcp://server:4840');
```

## Certificate Management API

The `CertificateManager` class provides utilities for working with certificates:

```php
use Gianfriaur\OpcuaPhpClient\Security\CertificateManager;

$cm = new CertificateManager();

// Load certificates (both PEM and DER)
$derBytes = $cm->loadCertificatePem('/path/to/cert.pem');
$derBytes = $cm->loadCertificateDer('/path/to/cert.der');

// Load private key
$privateKey = $cm->loadPrivateKeyPem('/path/to/key.pem');

// Certificate operations
$thumbprint = $cm->getThumbprint($derBytes);          // SHA1 hash (binary)
$keyLength = $cm->getPublicKeyLength($derBytes);       // bytes (e.g., 256 for 2048-bit)
$publicKey = $cm->getPublicKeyFromCert($derBytes);     // OpenSSLAsymmetricKey
$appUri = $cm->getApplicationUri($derBytes);           // from SAN extension
```

## MessageSecurity API

Low-level cryptographic operations:

```php
use Gianfriaur\OpcuaPhpClient\Security\MessageSecurity;

$ms = new MessageSecurity();

// Asymmetric operations (RSA)
$signature = $ms->asymmetricSign($data, $privateKey, $policy);
$valid = $ms->asymmetricVerify($data, $signature, $derCert, $policy);
$encrypted = $ms->asymmetricEncrypt($data, $derCert, $policy);
$decrypted = $ms->asymmetricDecrypt($data, $privateKey, $policy);

// Symmetric operations (AES + HMAC)
$signature = $ms->symmetricSign($data, $signingKey, $policy);
$valid = $ms->symmetricVerify($data, $signature, $signingKey, $policy);
$encrypted = $ms->symmetricEncrypt($data, $encKey, $iv, $policy);
$decrypted = $ms->symmetricDecrypt($data, $encKey, $iv, $policy);

// Key derivation (P_SHA1 / P_SHA256)
$keys = $ms->deriveKeys($secret, $seed, $policy);
// Returns: ['signingKey' => ..., 'encryptingKey' => ..., 'iv' => ...]
```

## How Security Works Internally

### Connection Flow with Security

1. **Discovery**: The client connects without security to call GetEndpoints and obtain the server's certificate
2. **Asymmetric Phase (OpenSecureChannel)**:
   - Client sends OPN request encrypted with server's public key
   - Both sides exchange nonces
   - Symmetric keys are derived from the shared nonces
3. **Symmetric Phase (Messages)**:
   - All MSG/CLO messages use derived symmetric keys
   - Messages are signed with HMAC, encrypted with AES-CBC
   - Padding follows OPC UA specification (PKCS#7 style)

### SecureChannel Lifecycle

The `SecureChannel` class manages:
- Asymmetric key exchange during OpenSecureChannel
- Symmetric key derivation from nonces
- Message signing, encryption, and padding
- Sequence number tracking
- Token and channel ID management

```
Client                          Server
  |                               |
  |--- HEL ---------------------->|
  |<-- ACK -----------------------|
  |                               |
  |--- OPN (asymmetric) --------->|  (encrypted with server public key)
  |<-- OPN response --------------|  (contains server nonce)
  |                               |
  |   [derive symmetric keys]     |
  |                               |
  |--- MSG (symmetric) ---------->|  (AES encrypted, HMAC signed)
  |<-- MSG (symmetric) ----------|
  |                               |
  |--- CLO ---------------------->|
```
