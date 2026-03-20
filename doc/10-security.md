# Security

## Security Policies

Each policy defines the algorithms used for encryption and signing:

| Policy | Asymmetric Sign | Asymmetric Encrypt | Symmetric Sign | Symmetric Encrypt | Min Key |
|--------|----------------|-------------------|---------------|-------------------|---------|
| None | -- | -- | -- | -- | -- |
| Basic128Rsa15 | RSA-SHA1 | RSA-PKCS1-v1_5 | HMAC-SHA1 | AES-128-CBC | 1024 bit |
| Basic256 | RSA-SHA1 | RSA-OAEP | HMAC-SHA1 | AES-256-CBC | 1024 bit |
| Basic256Sha256 | RSA-SHA256 | RSA-OAEP | HMAC-SHA256 | AES-256-CBC | 2048 bit |
| Aes128Sha256RsaOaep | RSA-SHA256 | RSA-OAEP | HMAC-SHA256 | AES-128-CBC | 2048 bit |
| Aes256Sha256RsaPss | RSA-PSS-SHA256 | RSA-OAEP-SHA256 | HMAC-SHA256 | AES-256-CBC | 2048 bit |

> **Tip:** For new deployments, use `Basic256Sha256` or `Aes256Sha256RsaPss`. The older policies (`Basic128Rsa15`, `Basic256`) exist for legacy server compatibility.

## Certificate Setup

### Generating Test Certificates

```bash
# 1. Create a CA
openssl genpkey -algorithm RSA -out ca.key -pkeyopt rsa_keygen_bits:2048
openssl req -x509 -new -key ca.key -days 365 -out ca.pem \
  -subj "/CN=Test CA"

# 2. Create a client certificate signed by the CA
openssl genpkey -algorithm RSA -out client.key -pkeyopt rsa_keygen_bits:2048
openssl req -new -key client.key -out client.csr \
  -subj "/CN=OPC UA Client" \
  -addext "subjectAltName=URI:urn:opcua-php-client:client"
openssl x509 -req -in client.csr -CA ca.pem -CAkey ca.key \
  -CAcreateserial -days 365 -out client.pem \
  -copy_extensions copy

# 3. (Optional) Convert to DER format
openssl x509 -in client.pem -outform der -out client.der
```

> **Note:** The `subjectAltName` URI is required by OPC UA. It must match the application URI your server expects.

## Client Configuration

```php
use Gianfriaur\OpcuaPhpClient\Client;
use Gianfriaur\OpcuaPhpClient\Security\SecurityPolicy;
use Gianfriaur\OpcuaPhpClient\Security\SecurityMode;

$client = new Client();

$client->setSecurityPolicy(SecurityPolicy::Basic256Sha256);
$client->setSecurityMode(SecurityMode::SignAndEncrypt);

$client->setClientCertificate(
    '/path/to/client.pem',   // PEM or DER, auto-detected
    '/path/to/client.key',
    '/path/to/ca.pem'        // optional: appended to the certificate chain
);

$client->connect('opc.tcp://server:4840');
```

If you skip `setClientCertificate()`, the library auto-generates a self-signed RSA 2048 certificate in memory with proper OPC UA extensions. This works for testing or servers configured with auto-accept trust.

> **Warning:** Auto-generated certificates are ephemeral. Every new `Client` instance gets a different certificate. For production, always provide your own.

## CertificateManager API

Utilities for loading and inspecting X.509 certificates:

```php
use Gianfriaur\OpcuaPhpClient\Security\CertificateManager;

$cm = new CertificateManager();

// Load certificates -- PEM and DER both work
$derBytes = $cm->loadCertificatePem('/path/to/cert.pem');
$derBytes = $cm->loadCertificateDer('/path/to/cert.der');

// Load a private key
$privateKey = $cm->loadPrivateKeyPem('/path/to/key.pem');

// Inspect
$thumbprint = $cm->getThumbprint($derBytes);         // SHA1 hash (binary)
$keyLength  = $cm->getPublicKeyLength($derBytes);     // bytes (256 = 2048-bit key)
$publicKey  = $cm->getPublicKeyFromCert($derBytes);   // OpenSSLAsymmetricKey
$appUri     = $cm->getApplicationUri($derBytes);      // from SAN extension
```

## MessageSecurity API

Low-level cryptographic operations. You rarely need these directly -- the `SecureChannel` handles them -- but they are available:

```php
use Gianfriaur\OpcuaPhpClient\Security\MessageSecurity;

$ms = new MessageSecurity();

// Asymmetric (RSA)
$signature = $ms->asymmetricSign($data, $privateKey, $policy);
$valid     = $ms->asymmetricVerify($data, $signature, $derCert, $policy);
$encrypted = $ms->asymmetricEncrypt($data, $derCert, $policy);
$decrypted = $ms->asymmetricDecrypt($data, $privateKey, $policy);

// Symmetric (AES + HMAC)
$signature = $ms->symmetricSign($data, $signingKey, $policy);
$valid     = $ms->symmetricVerify($data, $signature, $signingKey, $policy);
$encrypted = $ms->symmetricEncrypt($data, $encKey, $iv, $policy);
$decrypted = $ms->symmetricDecrypt($data, $encKey, $iv, $policy);

// Key derivation (P_SHA1 / P_SHA256)
$keys = $ms->deriveKeys($secret, $seed, $policy);
// Returns: ['signingKey' => ..., 'encryptingKey' => ..., 'iv' => ...]
```

## Connection Flow

Here is what happens when you call `connect()` with security enabled:

```
Client                          Server
  |                               |
  |--- HEL ---------------------->|  TCP handshake
  |<-- ACK -----------------------|
  |                               |
  |--- OPN (asymmetric) --------->|  Encrypted with server's public key
  |<-- OPN response --------------|  Contains server nonce
  |                               |
  |   [derive symmetric keys      |
  |    from shared nonces]        |
  |                               |
  |--- MSG (symmetric) ---------->|  AES encrypted, HMAC signed
  |<-- MSG (symmetric) ----------|
  |                               |
  |--- CLO ---------------------->|  Close secure channel
```

**Phase 1 -- Discovery.** The client connects without security, calls `GetEndpoints`, and retrieves the server's certificate.

**Phase 2 -- Asymmetric (OpenSecureChannel).** The client sends an OPN request encrypted with the server's public key. Both sides exchange nonces. Symmetric keys are derived from the shared nonces.

**Phase 3 -- Symmetric (Messages).** All `MSG` and `CLO` messages use the derived symmetric keys. Messages are signed with HMAC and encrypted with AES-CBC. Padding follows OPC UA spec (PKCS#7 style).

The `SecureChannel` class manages this entire lifecycle: asymmetric key exchange, symmetric key derivation, message signing/encryption/padding, sequence number tracking, and token/channel ID management.
