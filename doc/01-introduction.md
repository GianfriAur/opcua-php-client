# Introduction

## What is this

`gianfriaur/opcua-php-client` is an OPC UA client written entirely in PHP. It speaks the OPC UA binary protocol over TCP, handles secure channels, sessions, and crypto — all without external C/C++ extensions. The only requirement beyond PHP itself is `ext-openssl`.

## Requirements

- PHP >= 8.2
- `ext-openssl` (security and certificate handling)

## Installation

```bash
composer require gianfriaur/opcua-php-client
```

## What you can do with it

- **Binary Protocol** — full OPC UA binary encoding/decoding over TCP
- **Browse** — navigate the server address space, recursive browsing with automatic continuation
- **Path Resolution** — turn paths like `/Objects/MyPLC/Temperature` into NodeIds (TranslateBrowsePathsToNodeIds)
- **Read/Write** — read and write node attributes, single and multi
- **Method Call** — invoke OPC UA methods on the server
- **Subscriptions** — data change and event monitoring
- **History Read** — raw, processed, and at-time historical queries
- **Endpoint Discovery** — discover what the server offers
- **Security** — 6 security policies (None through Aes256Sha256RsaPss), 3 security modes
- **Authentication** — anonymous, username/password, X.509 certificate
- **Certificate Management** — PEM/DER loading, thumbprint, public key extraction
- **Timeout** — configurable timeout for connection and I/O
- **Connection State** — track connection lifecycle (Disconnected, Connected, Broken) with `reconnect()`
- **Auto-Retry** — automatic reconnect and retry on failure (default: 1 retry after first connect)
- **Auto-Batching** — transparent batching for `readMulti`/`writeMulti` with server limits discovery
- **ExtensionObject Codecs** — pluggable decoders for custom OPC UA structures

## Architecture

```
Client (main entry point)
  |
  +-- Transport/TcpTransport        (TCP socket communication)
  +-- Protocol/*Service              (OPC UA service encoding/decoding)
  +-- Encoding/BinaryEncoder         (binary serialization)
  +-- Encoding/BinaryDecoder         (binary deserialization)
  +-- Security/SecureChannel         (message-level security)
  +-- Security/MessageSecurity       (crypto operations)
  +-- Security/CertificateManager    (certificate handling)
  +-- Types/*                        (OPC UA data types)
  +-- Exception/*                    (error hierarchy)
```

## Quick Start

```php
use Gianfriaur\OpcuaPhpClient\Client;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;
use Gianfriaur\OpcuaPhpClient\Types\BuiltinType;
use Gianfriaur\OpcuaPhpClient\Types\StatusCode;

$client = new Client();
$client->connect('opc.tcp://localhost:4840');

// Read a value
$dataValue = $client->read(NodeId::numeric(0, 2259));
if (StatusCode::isGood($dataValue->getStatusCode())) {
    echo "Server status: " . $dataValue->getValue();
}

// Browse the Objects folder
$references = $client->browse(NodeId::numeric(0, 85));
foreach ($references as $ref) {
    echo $ref->getDisplayName() . "\n";
}

$client->disconnect();
```
