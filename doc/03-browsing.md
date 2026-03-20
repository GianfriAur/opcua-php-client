# Browsing the Address Space

## Browsing a Node

`browse()` returns the references (children) of a node:

```php
use Gianfriaur\OpcuaPhpClient\Types\NodeId;

$references = $client->browse(NodeId::numeric(0, 85)); // Objects folder

foreach ($references as $ref) {
    echo sprintf(
        "%s (NodeId: ns=%d;i=%s, Class: %s)\n",
        $ref->displayName,
        $ref->nodeId->namespaceIndex,
        $ref->nodeId->identifier,
        $ref->nodeClass->name,
    );
}
```

### Browse Parameters

```php
use Gianfriaur\OpcuaPhpClient\Types\BrowseDirection;

use Gianfriaur\OpcuaPhpClient\Types\NodeClass;

$references = $client->browse(
    nodeId: NodeId::numeric(0, 85),
    direction: BrowseDirection::Forward,
    referenceTypeId: NodeId::numeric(0, 33),  // HierarchicalReferences
    includeSubtypes: true,
    nodeClasses: [NodeClass::Object, NodeClass::Variable],  // filter by class
);
```

**Browse direction:**

| Direction | Value | Meaning |
|-----------|-------|---------|
| `Forward` | 0 | Children |
| `Inverse` | 1 | Parents |
| `Both` | 2 | Both directions |

**Common reference types:**

| NodeId | Name |
|--------|------|
| `NodeId::numeric(0, 33)` | HierarchicalReferences |
| `NodeId::numeric(0, 35)` | Organizes |
| `NodeId::numeric(0, 47)` | HasComponent |
| `NodeId::numeric(0, 46)` | HasProperty |
| `NodeId::numeric(0, 40)` | HasTypeDefinition |

**Node class filter:**

Pass an array of `NodeClass` enum values to filter results. Empty array (default) means all classes.

```php
use Gianfriaur\OpcuaPhpClient\Types\NodeClass;

// Only objects and variables
$refs = $client->browse($nodeId, nodeClasses: [NodeClass::Object, NodeClass::Variable]);

// Only methods
$refs = $client->browse($nodeId, nodeClasses: [NodeClass::Method]);

// All classes (default)
$refs = $client->browse($nodeId);
```

Available `NodeClass` values: `Object`, `Variable`, `Method`, `ObjectType`, `VariableType`, `ReferenceType`, `DataType`, `View`.

### ReferenceDescription Properties

Each reference returned by `browse()` has these properties:

```php
$ref->referenceTypeId;   // NodeId
$ref->isForward;         // bool
$ref->nodeId;            // NodeId
$ref->browseName;        // QualifiedName
$ref->displayName;       // LocalizedText
$ref->nodeClass;         // NodeClass enum
$ref->typeDefinition;    // ?NodeId
```

## Handling Continuation

Some servers paginate large result sets. You have two options.

### Automatic (recommended)

`browseAll()` follows all continuation points and returns the complete list:

```php
$refs = $client->browseAll(NodeId::numeric(0, 85));
```

### Manual

If you need control over pagination:

```php
$result = $client->browseWithContinuation(NodeId::numeric(0, 85));

$allRefs = $result->references;
$continuationPoint = $result->continuationPoint;

while ($continuationPoint !== null) {
    $next = $client->browseNext($continuationPoint);
    $allRefs = array_merge($allRefs, $next->references);
    $continuationPoint = $next->continuationPoint;
}
```

## Recursive Browse

`browseRecursive()` walks the address space from a starting node and builds a tree of `BrowseNode` objects. Continuation points are handled at each level, and cycle detection prevents infinite loops on circular references.

```php
$tree = $client->browseRecursive(NodeId::numeric(0, 85), maxDepth: 2);

foreach ($tree as $node) {
    echo $node->displayName . "\n";

    foreach ($node->getChildren() as $child) {
        echo "  " . $child->displayName . "\n";
    }
}
```

### Parameters

```php
$tree = $client->browseRecursive(
    nodeId: NodeId::numeric(0, 85),
    direction: BrowseDirection::Forward,
    maxDepth: 3,
    referenceTypeId: NodeId::numeric(0, 33),
    includeSubtypes: true,
    nodeClasses: [NodeClass::Object, NodeClass::Variable],
);
```

| Parameter | Default | Description |
|-----------|---------|-------------|
| `nodeId` | *(required)* | Starting node |
| `direction` | `Forward` | Browse direction |
| `maxDepth` | `null` (configured default: 10) | Max recursion depth. `-1` for unlimited (capped at 256) |
| `referenceTypeId` | `null` | Filter by reference type |
| `includeSubtypes` | `true` | Include reference subtypes |
| `nodeClasses` | `[]` | Filter by `NodeClass` enum values. Empty = all classes |

### Depth Limits

| `maxDepth` | Behavior |
|------------|----------|
| `null` | Uses configured default (10, or your `setDefaultBrowseMaxDepth()` value) |
| `1` | Direct children only |
| `-1` | Unlimited (capped at 256) |
| `> 256` | Capped at 256 |

You can change the default globally:

```php
$client = new Client();
$client->setDefaultBrowseMaxDepth(20);
$client->connect('opc.tcp://localhost:4840');

$tree = $client->browseRecursive($nodeId);              // uses 20
$tree = $client->browseRecursive($nodeId, maxDepth: 3); // override: 3
```

> **Warning:** High depth values can cause problems. Each level sends one browse request per node -- thousands of nodes means thousands of round-trips. Large trees eat memory, and massive browsing can overwhelm resource-constrained PLCs. Start small and increase only when needed.

### BrowseNode Properties

Each node in the tree wraps a `ReferenceDescription` and holds its children:

```php
$node->reference;      // ReferenceDescription
$node->nodeId;         // NodeId
$node->displayName;    // LocalizedText
$node->browseName;     // QualifiedName
$node->nodeClass;      // NodeClass enum
$node->getChildren();  // BrowseNode[]
$node->hasChildren();  // bool
```

### Cycle Detection

The method tracks every visited NodeId. If a node appears again, it is included as a leaf with no children, cutting the recursion. This matches the behavior of open62541, node-opcua, and the OPC Foundation .NET SDK.

### Printing a Tree

```php
function printTree(array $nodes, int $indent = 0): void
{
    foreach ($nodes as $node) {
        echo str_repeat('  ', $indent) . $node->displayName . "\n";
        printTree($node->getChildren(), $indent + 1);
    }
}

$tree = $client->browseRecursive(NodeId::numeric(0, 85), maxDepth: 3);
printTree($tree);
```

## Path Resolution

Instead of browsing step by step, resolve a human-readable path to a NodeId in one call:

```php
$nodeId = $client->resolveNodeId('/Objects/Server/ServerStatus/State');
$dataValue = $client->read($nodeId);
```

This calls `TranslateBrowsePathsToNodeIds` under the hood -- a single round-trip, much faster than manual browsing.

### Path Format

- Segments separated by `/`
- Leading `/` is optional
- Default start is Root (`ns=0;i=84`)
- For non-zero namespaces, use `ns:Name` format

```php
// Simple path
$nodeId = $client->resolveNodeId('/Objects/Server');

// With namespaced segments
$nodeId = $client->resolveNodeId('/Objects/2:MyPLC/2:Temperature');

// Custom starting node
$nodeId = $client->resolveNodeId('Server', NodeId::numeric(0, 85));
```

### Advanced: translateBrowsePaths

For full control over `TranslateBrowsePathsToNodeIds`, including resolving multiple paths in a single request:

```php
use Gianfriaur\OpcuaPhpClient\Types\QualifiedName;
use Gianfriaur\OpcuaPhpClient\Types\StatusCode;

$results = $client->translateBrowsePaths([
    [
        'startingNodeId' => NodeId::numeric(0, 85),
        'relativePath' => [
            ['targetName' => new QualifiedName(0, 'Server')],
            ['targetName' => new QualifiedName(0, 'ServerStatus')],
        ],
    ],
]);

if (StatusCode::isGood($results[0]->statusCode)) {
    $targetNodeId = $results[0]->targets[0]->targetId;
}
```

Each path element supports:

| Field | Default | Description |
|-------|---------|-------------|
| `targetName` | *(required)* | `QualifiedName` of the target |
| `referenceTypeId` | HierarchicalReferences | Reference type to follow |
| `isInverse` | `false` | Follow inverse references |
| `includeSubtypes` | `true` | Include subtypes |

## Well-Known NodeIds

| Name | NodeId | Description |
|------|--------|-------------|
| Root | `NodeId::numeric(0, 84)` | Root of the address space |
| Objects | `NodeId::numeric(0, 85)` | Objects folder |
| Types | `NodeId::numeric(0, 86)` | Types folder |
| Views | `NodeId::numeric(0, 87)` | Views folder |
| Server | `NodeId::numeric(0, 2253)` | Server object |
| ServerStatus | `NodeId::numeric(0, 2256)` | Server status |
| ServiceLevel | `NodeId::numeric(0, 2267)` | Service level |
