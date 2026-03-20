# History Read

OPC UA servers with historizing enabled can store past values. This library supports three types of historical queries: raw, processed, and at-time.

## Raw History

Get stored values within a time range, exactly as recorded:

```php
use Gianfriaur\OpcuaPhpClient\Types\NodeId;
use Gianfriaur\OpcuaPhpClient\Types\StatusCode;

$now = new \DateTimeImmutable();
$oneHourAgo = $now->modify('-1 hour');

$values = $client->historyReadRaw(
    'ns=2;i=1001',  // or NodeId::numeric(2, 1001)
    startTime: $oneHourAgo,
    endTime: $now,
    numValuesPerNode: 100,
    returnBounds: false,
);

foreach ($values as $dv) {
    echo sprintf(
        "[%s] %s (status: %s)\n",
        $dv->sourceTimestamp?->format('Y-m-d H:i:s.u'),
        $dv->getValue(),
        StatusCode::getName($dv->statusCode),
    );
}
```

### Parameters

| Parameter | Default | Description |
|---|---|---|
| `nodeId` | *(required)* | Node to read history from |
| `startTime` | `null` | Beginning of the time range |
| `endTime` | `null` | End of the time range |
| `numValuesPerNode` | `0` | Maximum values to return (`0` = no limit) |
| `returnBounds` | `false` | Include bounding values at the edges of the range |

## Processed History (Aggregates)

Get aggregated data over intervals. The server must support the HistoryRead service with processing:

```php
$startTime = new \DateTimeImmutable('2024-01-01 00:00:00');
$endTime = new \DateTimeImmutable('2024-01-02 00:00:00');

$values = $client->historyReadProcessed(
    'ns=2;i=1001',
    $startTime,
    $endTime,
    processingInterval: 3600000.0, // 1 hour in ms
    aggregateType: 'i=2342', // Average
);
```

### Common Aggregate Types

| Aggregate | NodeId |
|---|---|
| Average | `NodeId::numeric(0, 2342)` |
| Interpolative | `NodeId::numeric(0, 2341)` |
| Minimum | `NodeId::numeric(0, 2346)` |
| Maximum | `NodeId::numeric(0, 2347)` |
| Count | `NodeId::numeric(0, 2352)` |
| Total | `NodeId::numeric(0, 2344)` |

> **Note:** Not all servers support all aggregate types. Check your server documentation or use `getEndpoints()` to discover capabilities.

## History at Specific Times

Get interpolated values at exact timestamps:

```php
$timestamps = [
    new \DateTimeImmutable('2024-01-01 08:00:00'),
    new \DateTimeImmutable('2024-01-01 12:00:00'),
    new \DateTimeImmutable('2024-01-01 16:00:00'),
    new \DateTimeImmutable('2024-01-01 20:00:00'),
];

$values = $client->historyReadAtTime(
    'ns=2;i=1001',
    $timestamps,
);

foreach ($values as $i => $dv) {
    echo sprintf(
        "At %s: %s\n",
        $timestamps[$i]->format('H:i:s'),
        $dv->getValue(),
    );
}
```

> **Tip:** All three history methods return `DataValue[]`. Each `DataValue` includes `->statusCode`, `->sourceTimestamp`, and `->serverTimestamp` alongside the value itself.
