# Reading & Writing Values

## Reading

### Single Value

```php
use Gianfriaur\OpcuaPhpClient\Types\NodeId;
use Gianfriaur\OpcuaPhpClient\Types\StatusCode;

$dataValue = $client->read(NodeId::numeric(0, 2259)); // ServerStatus_State

if (StatusCode::isGood($dataValue->getStatusCode())) {
    $value = $dataValue->getValue();
    echo "Value: " . $value . "\n";
}
```

### Specific Attribute

By default `read()` targets the Value attribute (id=13). You can read others:

```php
use Gianfriaur\OpcuaPhpClient\Types\AttributeId;

// DisplayName
$displayName = $client->read(
    NodeId::numeric(0, 2259),
    AttributeId::DisplayName
);

// DataType
$dataType = $client->read(
    NodeId::numeric(0, 2259),
    AttributeId::DataType
);
```

**Common AttributeId values:**

| Constant | Value | What |
|----------|-------|------|
| `AttributeId::NodeId` | 1 | The node's NodeId |
| `AttributeId::NodeClass` | 2 | Node class |
| `AttributeId::BrowseName` | 3 | Browse name |
| `AttributeId::DisplayName` | 4 | Display name |
| `AttributeId::Description` | 5 | Description |
| `AttributeId::Value` | 13 | The value (default) |
| `AttributeId::DataType` | 14 | Data type NodeId |
| `AttributeId::AccessLevel` | 17 | Access level bitmask |

### Multiple Values

```php
$results = $client->readMulti([
    ['nodeId' => NodeId::numeric(0, 2259)],
    ['nodeId' => NodeId::numeric(0, 2267)],
    ['nodeId' => NodeId::numeric(2, 'Temperature'), 'attributeId' => AttributeId::Value],
]);

foreach ($results as $dataValue) {
    if (StatusCode::isGood($dataValue->getStatusCode())) {
        echo $dataValue->getValue() . "\n";
    }
}
```

### DataValue Properties

```php
$dataValue->getValue();             // mixed — unwrapped value
$dataValue->getVariant();           // ?Variant — typed variant
$dataValue->getStatusCode();        // int — OPC UA status code
$dataValue->getSourceTimestamp();    // ?DateTimeImmutable
$dataValue->getServerTimestamp();    // ?DateTimeImmutable
```

## Writing

### Single Value

```php
use Gianfriaur\OpcuaPhpClient\Types\BuiltinType;

$statusCode = $client->write(
    NodeId::numeric(2, 1234),
    42,
    BuiltinType::Int32
);

if (StatusCode::isGood($statusCode)) {
    echo "Write successful\n";
} else {
    echo "Write failed: " . StatusCode::getName($statusCode) . "\n";
}
```

### Multiple Values

```php
$results = $client->writeMulti([
    [
        'nodeId' => NodeId::numeric(2, 1001),
        'value' => 3.14,
        'type' => BuiltinType::Double,
    ],
    [
        'nodeId' => NodeId::numeric(2, 1002),
        'value' => 'Hello',
        'type' => BuiltinType::String,
    ],
    [
        'nodeId' => NodeId::numeric(2, 1003),
        'value' => true,
        'type' => BuiltinType::Boolean,
    ],
]);

foreach ($results as $i => $statusCode) {
    echo "Item $i: " . StatusCode::getName($statusCode) . "\n";
}
```

### Specific Attribute

By default write targets the Value attribute (id=13):

```php
$results = $client->writeMulti([
    [
        'nodeId' => NodeId::numeric(2, 1001),
        'value' => 100,
        'type' => BuiltinType::Int32,
        'attributeId' => 13, // Value attribute (default)
    ],
]);
```

## Automatic Batching

OPC UA servers can impose limits on how many nodes you can read/write in a single request (`MaxNodesPerRead`, `MaxNodesPerWrite`). The client handles this for you.

### Server Limits Discovery

After `connect()`, the client reads the server's limits from standard OPC UA nodes:
- `MaxNodesPerRead` (ns=0, i=11705)
- `MaxNodesPerWrite` (ns=0, i=11707)

A value of `0` means "no limit". You can check the discovered values:

```php
$client->connect('opc.tcp://localhost:4840');

echo $client->getServerMaxNodesPerRead();  // e.g. 100, or null if unknown
echo $client->getServerMaxNodesPerWrite(); // e.g. 100, or null if unknown
```

### How It Works

When `readMulti()` or `writeMulti()` gets more items than the batch size allows, it splits the request automatically and merges the results:

```php
$client->connect('opc.tcp://localhost:4840');
// Server says MaxNodesPerRead = 100

// This gets split into 10 requests of 100 each
$results = $client->readMulti($items1000);
// $results has all 1000 DataValues, in order
```

### Manual Batch Size

Override the server limit (or set one when the server doesn't report any):

```php
$client->setBatchSize(50); // max 50 nodes per request
$client->connect('opc.tcp://localhost:4840');

// readMulti and writeMulti batch at 50, regardless of server limits
```

**Priority:** `setBatchSize(N)` (N > 0) > server-reported limit > no batching

### Disabling Batching

To skip batching entirely — including the server limits discovery on `connect()`:

```php
$client->setBatchSize(0);
$client->connect('opc.tcp://localhost:4840');

// No discovery call, no batching. Everything goes in one request.
```

Useful if you know the server has no limits and want to save that extra read on connect.

### Batching Behavior

| `getBatchSize()` | Server reports | Discovery | Effective batch size |
|------------------|----------------|-----------|---------------------|
| `null` (default) | 100 | Yes | 100 |
| `null` (default) | 0 (no limit) | Yes | No batching |
| `null` (default) | Not supported | Yes | No batching |
| `50` | 100 | Yes | 50 |
| `50` | 0 | Yes | 50 |
| `0` (disabled) | Any | **Skipped** | No batching |

> **Note:** Batching only applies to `readMulti()` and `writeMulti()`. Single `read()` and `write()` always go as individual requests.

## Supported Data Types

| BuiltinType | PHP Type | Example |
|-------------|----------|---------|
| `Boolean` | `bool` | `true` |
| `SByte` | `int` | `-128` to `127` |
| `Byte` | `int` | `0` to `255` |
| `Int16` | `int` | `-32768` to `32767` |
| `UInt16` | `int` | `0` to `65535` |
| `Int32` | `int` | `-2^31` to `2^31-1` |
| `UInt32` | `int` | `0` to `2^32-1` |
| `Int64` | `int` | `-2^63` to `2^63-1` |
| `UInt64` | `int` | `0` to `2^64-1` |
| `Float` | `float` | `3.14` |
| `Double` | `float` | `3.141592653589793` |
| `String` | `string` | `'Hello'` |
| `DateTime` | `DateTimeImmutable` | `new DateTimeImmutable()` |
| `Guid` | `string` | `'550e8400-e29b-41d4-a716-446655440000'` |
| `ByteString` | `string` | Binary data |
| `NodeId` | `NodeId` | `NodeId::numeric(0, 85)` |
| `QualifiedName` | `QualifiedName` | `new QualifiedName(0, 'Name')` |
| `LocalizedText` | `LocalizedText` | `new LocalizedText('en', 'Text')` |

### Writing Arrays

```php
use Gianfriaur\OpcuaPhpClient\Types\Variant;
use Gianfriaur\OpcuaPhpClient\Types\DataValue;

// Pass PHP arrays as the Variant value
$variant = new Variant(BuiltinType::Int32, [1, 2, 3, 4, 5]);
$dataValue = new DataValue($variant);

// Or via writeMulti
$results = $client->writeMulti([
    [
        'nodeId' => NodeId::numeric(2, 2001),
        'value' => [10, 20, 30],
        'type' => BuiltinType::Int32,
    ],
]);
```

## Status Code Handling

```php
use Gianfriaur\OpcuaPhpClient\Types\StatusCode;

$statusCode = $dataValue->getStatusCode();

StatusCode::isGood($statusCode);      // true if 0x0XXXXXXX
StatusCode::isBad($statusCode);       // true if 0x8XXXXXXX
StatusCode::isUncertain($statusCode); // true if 0x4XXXXXXX
StatusCode::getName($statusCode);     // e.g. "BadNodeIdUnknown"
```

**Common status codes:**

| Constant | Value | Meaning |
|----------|-------|---------|
| `StatusCode::Good` | `0x00000000` | All good |
| `StatusCode::BadNodeIdUnknown` | `0x80340000` | Node doesn't exist |
| `StatusCode::BadTypeMismatch` | `0x80740000` | Value type mismatch |
| `StatusCode::BadNotWritable` | `0x803B0000` | Node is read-only |
| `StatusCode::BadNotReadable` | `0x803E0000` | Node is not readable |
| `StatusCode::BadUserAccessDenied` | `0x801F0000` | Access denied |
| `StatusCode::BadTimeout` | `0x800A0000` | Operation timed out |
