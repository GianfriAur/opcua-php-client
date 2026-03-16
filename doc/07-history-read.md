# History Read

## Overview

OPC UA servers with historizing capabilities can store past values. This library supports three types of historical queries:

- **Raw** - Read raw stored values in a time range
- **Processed** - Read aggregated values (e.g., average, min, max)
- **At Time** - Read interpolated values at specific timestamps

## Raw History Read

Read raw historical values within a time range:

```php
use Gianfriaur\OpcuaPhpClient\Types\NodeId;
use Gianfriaur\OpcuaPhpClient\Types\StatusCode;

$now = new \DateTimeImmutable();
$oneHourAgo = $now->modify('-1 hour');

$values = $client->historyReadRaw(
    NodeId::numeric(2, 1001),
    startTime: $oneHourAgo,
    endTime: $now,
    numValuesPerNode: 100,   // max values to return (0 = unlimited)
    returnBounds: false,      // include bounding values
);

foreach ($values as $dv) {
    echo sprintf(
        "[%s] %s (status: %s)\n",
        $dv->getSourceTimestamp()?->format('Y-m-d H:i:s.u'),
        $dv->getValue(),
        StatusCode::getName($dv->getStatusCode()),
    );
}
```

### Parameters

| Parameter | Default | Description |
|-----------|---------|-------------|
| `nodeId` | (required) | Node to read history from |
| `startTime` | `null` | Start of time range |
| `endTime` | `null` | End of time range |
| `numValuesPerNode` | `0` | Max values (0 = unlimited) |
| `returnBounds` | `false` | Include bounding values at edges |

## Processed History Read

Read aggregated historical data (requires server support):

```php
$startTime = new \DateTimeImmutable('2024-01-01 00:00:00');
$endTime = new \DateTimeImmutable('2024-01-02 00:00:00');

$values = $client->historyReadProcessed(
    NodeId::numeric(2, 1001),
    $startTime,
    $endTime,
    processingInterval: 3600000.0, // 1 hour intervals in ms
    aggregateType: NodeId::numeric(0, 2342), // Average aggregate
);
```

**Common aggregate type NodeIds:**

| Aggregate | NodeId |
|-----------|--------|
| Average | `NodeId::numeric(0, 2342)` |
| Interpolative | `NodeId::numeric(0, 2341)` |
| Minimum | `NodeId::numeric(0, 2346)` |
| Maximum | `NodeId::numeric(0, 2347)` |
| Count | `NodeId::numeric(0, 2352)` |
| Total | `NodeId::numeric(0, 2344)` |

## History Read At Time

Read interpolated values at specific points in time:

```php
$timestamps = [
    new \DateTimeImmutable('2024-01-01 08:00:00'),
    new \DateTimeImmutable('2024-01-01 12:00:00'),
    new \DateTimeImmutable('2024-01-01 16:00:00'),
    new \DateTimeImmutable('2024-01-01 20:00:00'),
];

$values = $client->historyReadAtTime(
    NodeId::numeric(2, 1001),
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
