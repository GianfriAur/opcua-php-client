# Method Calling

## Basic Method Call

OPC UA methods are invoked on a target object. You need both the object's NodeId and the method's NodeId:

```php
use Gianfriaur\OpcuaPhpClient\Types\NodeId;
use Gianfriaur\OpcuaPhpClient\Types\Variant;
use Gianfriaur\OpcuaPhpClient\Types\BuiltinType;
use Gianfriaur\OpcuaPhpClient\Types\StatusCode;

$result = $client->call(
    NodeId::numeric(0, 2253),  // Server object
    NodeId::numeric(0, 11492), // GetMonitoredItems method
    [
        new Variant(BuiltinType::UInt32, 1), // subscriptionId argument
    ]
);
```

## Call Result

The `call()` method returns an associative array:

```php
$result = $client->call($objectId, $methodId, $args);

$statusCode = $result['statusCode'];           // int - method execution status
$inputResults = $result['inputArgumentResults']; // int[] - per-argument validation
$outputs = $result['outputArguments'];           // Variant[] - output values

// Check if the method succeeded
if (StatusCode::isGood($statusCode)) {
    foreach ($outputs as $output) {
        echo "Type: " . $output->getType()->name . "\n";
        echo "Value: " . print_r($output->getValue(), true) . "\n";
    }
}
```

## Call Without Arguments

```php
$result = $client->call(
    NodeId::numeric(2, 1000),  // target object
    NodeId::numeric(2, 1001),  // method with no input arguments
);
```

## Call With Multiple Arguments

```php
$result = $client->call(
    $objectNodeId,
    $methodNodeId,
    [
        new Variant(BuiltinType::String, 'parameter1'),
        new Variant(BuiltinType::Double, 3.14),
        new Variant(BuiltinType::Boolean, true),
        new Variant(BuiltinType::Int32, [1, 2, 3]), // array argument
    ]
);
```

## Error Handling

```php
$result = $client->call($objectId, $methodId, $args);

if (StatusCode::isBad($result['statusCode'])) {
    echo "Method failed: " . StatusCode::getName($result['statusCode']) . "\n";
}

// Check individual argument validation
foreach ($result['inputArgumentResults'] as $i => $argStatus) {
    if (StatusCode::isBad($argStatus)) {
        echo "Argument $i rejected: " . StatusCode::getName($argStatus) . "\n";
    }
}
```
