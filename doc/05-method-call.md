# Method Calling

## Calling a Method

OPC UA methods live on object nodes. You need the object's NodeId and the method's NodeId:

```php
use Gianfriaur\OpcuaPhpClient\Types\NodeId;
use Gianfriaur\OpcuaPhpClient\Types\Variant;
use Gianfriaur\OpcuaPhpClient\Types\BuiltinType;
use Gianfriaur\OpcuaPhpClient\Types\StatusCode;

$result = $client->call(
    'i=2253',   // Server object (or NodeId::numeric(0, 2253))
    'i=11492',  // GetMonitoredItems method
    [
        new Variant(BuiltinType::UInt32, 1), // subscriptionId argument
    ]
);

if (StatusCode::isGood($result->statusCode)) {
    foreach ($result->outputArguments as $output) {
        echo $output->type->name . ': ' . print_r($output->value, true) . "\n";
    }
}
```

`call()` returns a [`CallResult`](08-types.md#callresult) with three properties:

- `$result->statusCode` -- overall method execution status
- `$result->inputArgumentResults` -- per-argument validation status codes (`int[]`)
- `$result->outputArguments` -- return values (`Variant[]`)

## Without Arguments

If the method takes no inputs, just omit the third parameter:

```php
$result = $client->call(
    NodeId::numeric(2, 1000),
    NodeId::numeric(2, 1001),
);
```

## Multiple Arguments

Pass each argument as a `Variant` with the correct type:

```php
$result = $client->call(
    $objectNodeId,
    $methodNodeId,
    [
        new Variant(BuiltinType::String, 'hello'),
        new Variant(BuiltinType::Double, 3.14),
        new Variant(BuiltinType::Boolean, true),
        new Variant(BuiltinType::Int32, [1, 2, 3]), // array argument
    ]
);
```

> **Tip:** The server defines what types each argument expects. If you send the wrong type, you will get a `BadTypeMismatch` status on that argument.

## Error Handling

Check the overall status first, then inspect individual arguments if needed:

```php
$result = $client->call($objectId, $methodId, $args);

// Overall method status
if (StatusCode::isBad($result->statusCode)) {
    echo 'Method failed: ' . StatusCode::getName($result->statusCode) . "\n";
}

// Per-argument validation
foreach ($result->inputArgumentResults as $i => $argStatus) {
    if (StatusCode::isBad($argStatus)) {
        echo "Argument $i rejected: " . StatusCode::getName($argStatus) . "\n";
    }
}
```

> **Note:** A bad `statusCode` means the method itself failed. Bad entries in `inputArgumentResults` mean specific arguments were rejected -- the method may not have executed at all.
