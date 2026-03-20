# Subscriptions & Monitoring

## How it works

OPC UA subscriptions let you get notifications when values change or events fire, instead of polling. The flow:

1. Create a subscription
2. Add monitored items to it
3. Call `publish()` to get notifications
4. Clean up when done

## Creating a Subscription

```php
$sub = $client->createSubscription(
    publishingInterval: 1000.0,   // ms between publish cycles
    lifetimeCount: 2400,          // cycles before the subscription expires
    maxKeepAliveCount: 10,        // cycles before an empty notification
    maxNotificationsPerPublish: 0, // 0 = unlimited
    publishingEnabled: true,
    priority: 0,
);

$subscriptionId = $sub['subscriptionId'];
echo "Subscription created: " . $subscriptionId . "\n";
echo "Revised interval: " . $sub['revisedPublishingInterval'] . " ms\n";
```

## Data Change Monitoring

Watch nodes for value changes:

```php
use Gianfriaur\OpcuaPhpClient\Types\NodeId;

$results = $client->createMonitoredItems(
    $subscriptionId,
    [
        [
            'nodeId' => NodeId::numeric(0, 2258),    // CurrentTime
            'samplingInterval' => 500.0,              // ms (optional, default: -1 = server picks)
            'queueSize' => 10,                        // max queued values (optional, default: 1)
            'clientHandle' => 1,                      // your identifier (optional, default: auto)
        ],
        [
            'nodeId' => NodeId::numeric(2, 1001),
            'attributeId' => 13,                      // Value attribute (optional, default: 13)
        ],
    ]
);

foreach ($results as $result) {
    echo "MonitoredItem " . $result['monitoredItemId']
        . " status: " . StatusCode::getName($result['statusCode']) . "\n";
}
```

### MonitoredItem Parameters

| Parameter | Default | What |
|-----------|---------|------|
| `nodeId` | (required) | Node to monitor |
| `attributeId` | 13 (Value) | Which attribute |
| `samplingInterval` | -1.0 | Sampling rate in ms (-1 = server default) |
| `queueSize` | 1 | Max queued notifications |
| `clientHandle` | auto | Client-side ID for matching notifications |
| `monitoringMode` | 2 (Reporting) | 0=Disabled, 1=Sampling, 2=Reporting |

## Event Monitoring

Watch a node for OPC UA events:

```php
$result = $client->createEventMonitoredItem(
    $subscriptionId,
    NodeId::numeric(0, 2253),  // Server object
    ['EventId', 'EventType', 'SourceName', 'Time', 'Message', 'Severity'],
    clientHandle: 1,
);

echo "Event monitor status: " . StatusCode::getName($result['statusCode']) . "\n";
```

Default fields: `EventId`, `EventType`, `SourceName`, `Time`, `Message`, `Severity`.

## Receiving Notifications

Call `publish()` to get what's pending:

```php
$response = $client->publish();

echo "Subscription: " . $response['subscriptionId'] . "\n";
echo "Sequence: " . $response['sequenceNumber'] . "\n";
echo "More: " . ($response['moreNotifications'] ? 'yes' : 'no') . "\n";

foreach ($response['notifications'] as $notif) {
    if ($notif['type'] === 'DataChange') {
        echo "Handle " . $notif['clientHandle']
            . ": " . $notif['dataValue']->getValue() . "\n";
    } elseif ($notif['type'] === 'Event') {
        echo "Event handle " . $notif['clientHandle'] . ":\n";
        foreach ($notif['eventFields'] as $field) {
            echo "  " . $field->getValue() . "\n";
        }
    }
}
```

### Acknowledging Notifications

Pass ack info to `publish()` to avoid re-receiving:

```php
$response = $client->publish();

// next publish acknowledges the previous one
$response2 = $client->publish([
    [
        'subscriptionId' => $response['subscriptionId'],
        'sequenceNumber' => $response['sequenceNumber'],
    ],
]);
```

## Polling Loop

```php
$sub = $client->createSubscription(publishingInterval: 500.0);

$client->createMonitoredItems($sub['subscriptionId'], [
    ['nodeId' => NodeId::numeric(2, 1001)],
]);

$lastAck = [];

for ($i = 0; $i < 100; $i++) {
    $response = $client->publish($lastAck);

    foreach ($response['notifications'] as $notif) {
        echo $notif['dataValue']->getValue() . "\n";
    }

    $lastAck = [[
        'subscriptionId' => $response['subscriptionId'],
        'sequenceNumber' => $response['sequenceNumber'],
    ]];
}
```

## Cleanup

```php
// Delete specific monitored items
$statuses = $client->deleteMonitoredItems(
    $subscriptionId,
    [$monitoredItemId1, $monitoredItemId2]
);

// Delete the subscription
$status = $client->deleteSubscription($subscriptionId);
```

Subscriptions also get cleaned up automatically when you call `$client->disconnect()`.
