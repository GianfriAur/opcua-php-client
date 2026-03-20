# History Read

## What's available

OPC UA servers with historizing can store past values. Three types of historical queries:

- **Raw** — stored values in a time range, as-is
- **Processed** — aggregated values (average, min, max, etc.)
- **At Time** — interpolated values at specific timestamps

## Raw History Read

Get raw values within a time range:

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

| Parameter | Default | What |
|-----------|---------|------|
| `nodeId` | (required) | Node to read history from |
| `startTime` | `null` | Start of time range |
| `endTime` | `null` | End of time range |
| `numValuesPerNode` | `0` | Max values (0 = unlimited) |
| `returnBounds` | `false` | Include bounding values at the edges |

## Processed History Read

Get aggregated data (server must support it):

```php
$startTime = new \DateTimeImmutable('2024-01-01 00:00:00');
$endTime = new \DateTimeImmutable('2024-01-02 00:00:00');

$values = $client->historyReadProcessed(
    NodeId::numeric(2, 1001),
    $startTime,
    $endTime,
    processingInterval: 3600000.0, // 1 hour intervals in ms
    aggregateType: NodeId::numeric(0, 2342), // Average
);
```

**Common aggregate types:**

| Aggregate | NodeId |
|-----------|--------|
| Average | `NodeId::numeric(0, 2342)` |
| Interpolative | `NodeId::numeric(0, 2341)` |
| Minimum | `NodeId::numeric(0, 2346)` |
| Maximum | `NodeId::numeric(0, 2347)` |
| Count | `NodeId::numeric(0, 2352)` |
| Total | `NodeId::numeric(0, 2344)` |

## History Read At Time

Get interpolated values at specific points in time:

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
