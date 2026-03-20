# Subscriptions & Monitoring

## Overview

Subscriptions let you receive notifications when values change, instead of polling with `read()`. The workflow:

1. Create a subscription
2. Add monitored items to it
3. Call `publish()` to collect notifications
4. Clean up when done

## Creating a Subscription

```php
$sub = $client->createSubscription(
    publishingInterval: 1000.0,
    lifetimeCount: 2400,
    maxKeepAliveCount: 10,
    maxNotificationsPerPublish: 0,
    publishingEnabled: true,
    priority: 0,
);

echo 'Subscription ID: ' . $sub->subscriptionId . "\n";
echo 'Revised interval: ' . $sub->revisedPublishingInterval . " ms\n";
```

Returns a [`SubscriptionResult`](08-types.md#subscriptionresult). The server may revise your requested intervals -- always check the `revised*` properties.

## Monitoring Data Changes

Add nodes to watch for value changes:

```php
use Gianfriaur\OpcuaPhpClient\Types\NodeId;
use Gianfriaur\OpcuaPhpClient\Types\StatusCode;

// Fluent builder
$results = $client->createMonitoredItems($sub->subscriptionId)
    ->add('i=2258')->samplingInterval(500.0)->queueSize(10)->clientHandle(1)
    ->add('ns=2;i=1001')
    ->execute();

foreach ($results as $result) {
    echo 'Item ' . $result->monitoredItemId
        . ': ' . StatusCode::getName($result->statusCode) . "\n";
}

// Or with array (still works)
$results = $client->createMonitoredItems(
    $sub->subscriptionId,
    [
        [
            'nodeId' => NodeId::numeric(0, 2258),   // CurrentTime
            'samplingInterval' => 500.0,
            'queueSize' => 10,
            'clientHandle' => 1,
        ],
        [
            'nodeId' => NodeId::numeric(2, 1001),
        ],
    ]
);
```

> **Tip:** The builder's `->add()` starts a new monitored item. Chain `->samplingInterval()`, `->queueSize()`, and `->clientHandle()` to configure it. Unset options use server defaults.

### Monitored Item Parameters

| Parameter | Default | Description |
|---|---|---|
| `nodeId` | *(required)* | Node to monitor |
| `attributeId` | `13` (Value) | Which attribute to watch |
| `samplingInterval` | `-1.0` | Sampling rate in ms (`-1` = server decides) |
| `queueSize` | `1` | Max queued notifications before oldest is dropped |
| `clientHandle` | auto | Your identifier -- comes back in notifications |
| `monitoringMode` | `2` (Reporting) | `0` = Disabled, `1` = Sampling, `2` = Reporting |

## Monitoring Events

Watch a node for OPC UA events:

```php
$result = $client->createEventMonitoredItem(
    $sub->subscriptionId,
    NodeId::numeric(0, 2253), // Server object
    ['EventId', 'EventType', 'SourceName', 'Time', 'Message', 'Severity'],
    clientHandle: 1,
);

echo 'Status: ' . StatusCode::getName($result->statusCode) . "\n";
```

The field list defaults to `EventId`, `EventType`, `SourceName`, `Time`, `Message`, `Severity` if you omit it.

## Receiving Notifications

Call `publish()` to get pending notifications:

```php
$response = $client->publish();

echo 'Subscription: ' . $response->subscriptionId . "\n";
echo 'More waiting: ' . ($response->moreNotifications ? 'yes' : 'no') . "\n";

foreach ($response->notifications as $notif) {
    if ($notif['type'] === 'DataChange') {
        echo 'Handle ' . $notif['clientHandle']
            . ': ' . $notif['dataValue']->getValue() . "\n";
    }

    if ($notif['type'] === 'Event') {
        echo 'Event on handle ' . $notif['clientHandle'] . ":\n";
        foreach ($notif['eventFields'] as $field) {
            echo '  ' . $field->value . "\n";
        }
    }
}
```

`publish()` returns a [`PublishResult`](08-types.md#publishresult).

### Acknowledging Notifications

Pass acknowledgment info to `publish()` so the server stops resending:

```php
$response = $client->publish();

// Acknowledge the previous notification on the next publish call
$response2 = $client->publish([
    [
        'subscriptionId' => $response->subscriptionId,
        'sequenceNumber' => $response->sequenceNumber,
    ],
]);
```

## Full Polling Loop

Here is a complete example that creates a subscription, monitors a node, and processes notifications in a loop:

```php
$sub = $client->createSubscription(publishingInterval: 500.0);

$client->createMonitoredItems($sub->subscriptionId, [
    ['nodeId' => NodeId::numeric(2, 1001)],
]);

$lastAck = [];

for ($i = 0; $i < 100; $i++) {
    $response = $client->publish($lastAck);

    foreach ($response->notifications as $notif) {
        echo $notif['dataValue']->getValue() . "\n";
    }

    $lastAck = [[
        'subscriptionId' => $response->subscriptionId,
        'sequenceNumber' => $response->sequenceNumber,
    ]];
}
```

## Cleanup

Delete monitored items or the entire subscription when you are done:

```php
// Remove specific monitored items
$statuses = $client->deleteMonitoredItems(
    $subscriptionId,
    [$monitoredItemId1, $monitoredItemId2]
);

// Remove the subscription
$status = $client->deleteSubscription($subscriptionId);
```

> **Tip:** Subscriptions are automatically cleaned up when you call `$client->disconnect()`.
