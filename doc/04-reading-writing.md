# Reading & Writing Values

## Reading

### Read a Single Value

```php
use Gianfriaur\OpcuaPhpClient\Types\NodeId;
use Gianfriaur\OpcuaPhpClient\Types\StatusCode;

$dataValue = $client->read(NodeId::numeric(0, 2259)); // ServerStatus_State

if (StatusCode::isGood($dataValue->getStatusCode())) {
    $value = $dataValue->getValue();
    echo "Value: " . $value . "\n";
}
```

### Read a Specific Attribute

By default, `read()` reads the Value attribute (id=13). You can read other attributes:

```php
use Gianfriaur\OpcuaPhpClient\Types\AttributeId;

// Read the DisplayName attribute
$displayName = $client->read(
    NodeId::numeric(0, 2259),
    AttributeId::DisplayName
);

// Read the DataType attribute
$dataType = $client->read(
    NodeId::numeric(0, 2259),
    AttributeId::DataType
);
```

**Common AttributeId values:**

| Constant | Value | Description |
|----------|-------|-------------|
| `AttributeId::NodeId` | 1 | The NodeId of the node |
| `AttributeId::NodeClass` | 2 | The class of the node |
| `AttributeId::BrowseName` | 3 | The browse name |
| `AttributeId::DisplayName` | 4 | The display name |
| `AttributeId::Description` | 5 | The description |
| `AttributeId::Value` | 13 | The value (default) |
| `AttributeId::DataType` | 14 | The data type NodeId |
| `AttributeId::AccessLevel` | 17 | Access level bitmask |

### Read Multiple Values

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

The `DataValue` object contains:

```php
$dataValue->getValue();             // mixed - the unwrapped value
$dataValue->getVariant();           // ?Variant - the typed variant
$dataValue->getStatusCode();        // int - OPC UA status code
$dataValue->getSourceTimestamp();    // ?DateTimeImmutable
$dataValue->getServerTimestamp();    // ?DateTimeImmutable
```

## Writing

### Write a Single Value

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

### Write Multiple Values

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

### Write to a Specific Attribute

By default, write targets the Value attribute (id=13):

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

OPC UA servers may impose limits on the number of nodes per request (`MaxNodesPerRead`, `MaxNodesPerWrite`). The client handles this transparently.

### Server Limits Discovery

After `connect()`, the client automatically reads the server's operation limits from the standard OPC UA nodes:
- `MaxNodesPerRead` (ns=0, i=11705)
- `MaxNodesPerWrite` (ns=0, i=11707)

A value of `0` means "no limit". You can inspect the discovered values:

```php
$client->connect('opc.tcp://localhost:4840');

echo $client->getServerMaxNodesPerRead();  // e.g. 100, or null if unknown
echo $client->getServerMaxNodesPerWrite(); // e.g. 100, or null if unknown
```

### Transparent Batching

When the number of items in `readMulti()` or `writeMulti()` exceeds the effective batch size, the client automatically splits the request into multiple smaller requests and merges the results:

```php
$client->connect('opc.tcp://localhost:4840');
// Server reports MaxNodesPerRead = 100

// This will be split into 10 requests of 100 nodes each
$results = $client->readMulti($items1000);
// $results contains all 1000 DataValues in order
```

### Manual Batch Size

You can override the server limit (or set a batch size when the server doesn't report one) using `setBatchSize()`:

```php
$client->setBatchSize(50); // max 50 nodes per request
$client->connect('opc.tcp://localhost:4840');

// readMulti and writeMulti will batch at 50 regardless of server limits
```

**Priority order:** `setBatchSize(N)` (N > 0) > server-reported limit > no batching

### Disabling Auto-Batching

To disable batching entirely — including skipping the server operation limits discovery on `connect()`:

```php
$client->setBatchSize(0);
$client->connect('opc.tcp://localhost:4840');

// No discovery call is made, no batching is applied
// All items are sent in a single request regardless of server limits
```

This can be useful to avoid the extra read request during connection, or when you know the server has no limits and want to minimize overhead.

### Batching Behavior

| `getBatchSize()` | Server reports | Discovery | Effective batch size |
|------------------|----------------|-----------|---------------------|
| `null` (default) | 100 | Yes | 100 |
| `null` (default) | 0 (no limit) | Yes | No batching |
| `null` (default) | Not supported | Yes | No batching |
| `50` | 100 | Yes | 50 |
| `50` | 0 | Yes | 50 |
| `0` (disabled) | Any | **Skipped** | No batching |

> **Note:** Batching applies only to `readMulti()` and `writeMulti()`. Single `read()` and `write()` are always sent as individual requests.

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

// Arrays are passed as PHP arrays in the Variant value
$variant = new Variant(BuiltinType::Int32, [1, 2, 3, 4, 5]);
$dataValue = new DataValue($variant);

// Using writeMulti with raw DataValue for more control
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

| Constant | Value | Description |
|----------|-------|-------------|
| `StatusCode::Good` | `0x00000000` | Operation succeeded |
| `StatusCode::BadNodeIdUnknown` | `0x80340000` | Node does not exist |
| `StatusCode::BadTypeMismatch` | `0x80740000` | Value type mismatch |
| `StatusCode::BadNotWritable` | `0x803B0000` | Node is read-only |
| `StatusCode::BadNotReadable` | `0x803E0000` | Node is not readable |
| `StatusCode::BadUserAccessDenied` | `0x801F0000` | Access denied |
| `StatusCode::BadTimeout` | `0x800A0000` | Operation timed out |
