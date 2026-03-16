# Browsing the Address Space

## Basic Browse

Browse returns the references (children) of a given node:

```php
use Gianfriaur\OpcuaPhpClient\Types\NodeId;

// Browse the Objects folder (ns=0, i=85)
$references = $client->browse(NodeId::numeric(0, 85));

foreach ($references as $ref) {
    echo sprintf(
        "%s (NodeId: ns=%d;i=%s, Class: %s)\n",
        $ref->getDisplayName(),
        $ref->getNodeId()->getNamespaceIndex(),
        $ref->getNodeId()->getIdentifier(),
        $ref->getNodeClass()->name,
    );
}
```

## Browse Parameters

```php
$references = $client->browse(
    nodeId: NodeId::numeric(0, 85),
    direction: 0,                               // 0=Forward, 1=Inverse, 2=Both
    referenceTypeId: NodeId::numeric(0, 33),     // HierarchicalReferences (default)
    includeSubtypes: true,                       // Include subtypes of reference
    nodeClassMask: 0,                            // 0=All classes
);
```

**Direction values:**
- `0` - Forward references (children)
- `1` - Inverse references (parents)
- `2` - Both directions

**Common reference type NodeIds:**
- `NodeId::numeric(0, 33)` - HierarchicalReferences
- `NodeId::numeric(0, 35)` - Organizes
- `NodeId::numeric(0, 47)` - HasComponent
- `NodeId::numeric(0, 46)` - HasProperty
- `NodeId::numeric(0, 40)` - HasTypeDefinition

**NodeClass mask (bitmask):**
- `0` - All classes
- `1` - Object
- `2` - Variable
- `4` - Method
- `8` - ObjectType
- `16` - VariableType
- `32` - ReferenceType
- `64` - DataType
- `128` - View

## Browse with Continuation

For large result sets, the server may return a continuation point:

```php
$result = $client->browseWithContinuation(NodeId::numeric(0, 85));

$allRefs = $result['references'];
$continuationPoint = $result['continuationPoint'];

// Fetch remaining results
while ($continuationPoint !== null) {
    $next = $client->browseNext($continuationPoint);
    $allRefs = array_merge($allRefs, $next['references']);
    $continuationPoint = $next['continuationPoint'];
}
```

## ReferenceDescription Properties

Each reference returned by browse contains:

```php
$ref->getReferenceTypeId();   // NodeId - type of relationship
$ref->isForward();            // bool - direction of reference
$ref->getNodeId();            // NodeId - target node
$ref->getBrowseName();        // QualifiedName - browse name
$ref->getDisplayName();       // LocalizedText - human-readable name
$ref->getNodeClass();         // NodeClass enum
$ref->getTypeDefinition();    // ?NodeId - type definition node
```

## Common Well-Known NodeIds

| Name | NodeId | Description |
|------|--------|-------------|
| Root | `NodeId::numeric(0, 84)` | Root of the address space |
| Objects | `NodeId::numeric(0, 85)` | Objects folder |
| Types | `NodeId::numeric(0, 86)` | Types folder |
| Views | `NodeId::numeric(0, 87)` | Views folder |
| Server | `NodeId::numeric(0, 2253)` | Server object |
| ServerStatus | `NodeId::numeric(0, 2256)` | Server status |
| ServiceLevel | `NodeId::numeric(0, 2267)` | Service level |

## Recursive Browse Example

```php
function browseRecursive(Client $client, NodeId $nodeId, int $depth = 0): void
{
    $refs = $client->browse($nodeId);
    foreach ($refs as $ref) {
        echo str_repeat('  ', $depth) . $ref->getDisplayName() . "\n";
        if ($ref->getNodeClass() === NodeClass::Object) {
            browseRecursive($client, $ref->getNodeId(), $depth + 1);
        }
    }
}

browseRecursive($client, NodeId::numeric(0, 85));
```
