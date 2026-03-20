# Browsing the Address Space

## Basic Browse

`browse()` returns the references (children) of a node:

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
use Gianfriaur\OpcuaPhpClient\Types\BrowseDirection;

$references = $client->browse(
    nodeId: NodeId::numeric(0, 85),
    direction: BrowseDirection::Forward,          // Forward, Inverse, or Both
    referenceTypeId: NodeId::numeric(0, 33),     // HierarchicalReferences (default)
    includeSubtypes: true,                       // include subtypes of reference
    nodeClassMask: 0,                            // 0 = all classes
);
```

**BrowseDirection:**

| Case | Value | What it does |
|------|-------|--------------|
| `BrowseDirection::Forward` | `0` | Forward references (children) |
| `BrowseDirection::Inverse` | `1` | Inverse references (parents) |
| `BrowseDirection::Both` | `2` | Both directions |

**Common reference type NodeIds:**
- `NodeId::numeric(0, 33)` — HierarchicalReferences
- `NodeId::numeric(0, 35)` — Organizes
- `NodeId::numeric(0, 47)` — HasComponent
- `NodeId::numeric(0, 46)` — HasProperty
- `NodeId::numeric(0, 40)` — HasTypeDefinition

**NodeClass mask (bitmask):**
- `0` — All classes
- `1` — Object
- `2` — Variable
- `4` — Method
- `8` — ObjectType
- `16` — VariableType
- `32` — ReferenceType
- `64` — DataType
- `128` — View

## Browse All (automatic continuation)

`browseAll()` works like `browse()` but follows all continuation points automatically, so you get the complete list in one call:

```php
$refs = $client->browseAll(NodeId::numeric(0, 85));
// all references, even if the server paginates
```

Same result as the manual continuation loop below, but without the hassle.

## Browse with Continuation (manual)

If you need fine-grained control over pagination:

```php
$result = $client->browseWithContinuation(NodeId::numeric(0, 85));

$allRefs = $result['references'];
$continuationPoint = $result['continuationPoint'];

while ($continuationPoint !== null) {
    $next = $client->browseNext($continuationPoint);
    $allRefs = array_merge($allRefs, $next['references']);
    $continuationPoint = $next['continuationPoint'];
}
```

## ReferenceDescription Properties

Each reference has:

```php
$ref->getReferenceTypeId();   // NodeId — type of relationship
$ref->isForward();            // bool — direction
$ref->getNodeId();            // NodeId — target node
$ref->getBrowseName();        // QualifiedName
$ref->getDisplayName();       // LocalizedText — human-readable name
$ref->getNodeClass();         // NodeClass enum
$ref->getTypeDefinition();    // ?NodeId — type definition node
```

## Well-Known NodeIds

| Name | NodeId | What |
|------|--------|------|
| Root | `NodeId::numeric(0, 84)` | Root of the address space |
| Objects | `NodeId::numeric(0, 85)` | Objects folder |
| Types | `NodeId::numeric(0, 86)` | Types folder |
| Views | `NodeId::numeric(0, 87)` | Views folder |
| Server | `NodeId::numeric(0, 2253)` | Server object |
| ServerStatus | `NodeId::numeric(0, 2256)` | Server status |
| ServiceLevel | `NodeId::numeric(0, 2267)` | Service level |

## Recursive Browse

`browseRecursive()` walks the address space recursively from a starting node and builds a tree of `BrowseNode` objects. Continuation points are handled at each level, and there's built-in **cycle detection** to avoid infinite loops on circular references.

```php
use Gianfriaur\OpcuaPhpClient\Types\BrowseNode;

// Browse 2 levels deep from Objects
$tree = $client->browseRecursive(NodeId::numeric(0, 85), maxDepth: 2);

foreach ($tree as $node) {
    echo $node->getDisplayName() . "\n";
    foreach ($node->getChildren() as $child) {
        echo "  " . $child->getDisplayName() . "\n";
    }
}
```

### Default Depth

The default `maxDepth` is 10. You can change it globally:

```php
$client = new Client();
$client->setDefaultBrowseMaxDepth(20); // all browseRecursive() calls use 20

$client->connect('opc.tcp://localhost:4840');

$tree = $client->browseRecursive($nodeId);                // uses 20
$tree = $client->browseRecursive($nodeId, maxDepth: 3);   // override: 3
```

### Parameters

```php
$tree = $client->browseRecursive(
    nodeId: NodeId::numeric(0, 85),
    direction: BrowseDirection::Forward,              // Forward, Inverse, or Both
    maxDepth: 3,                                      // default: 10, use -1 for unlimited
    referenceTypeId: NodeId::numeric(0, 33),          // HierarchicalReferences
    includeSubtypes: true,
    nodeClassMask: 0,                                 // 0 = all
);
```

| Parameter | Default | What |
|-----------|---------|------|
| `nodeId` | (required) | Starting node |
| `direction` | `BrowseDirection::Forward` | Browse direction |
| `maxDepth` | `null` (uses configured default: 10) | Max recursion depth. `-1` for unlimited (capped at 256) |
| `referenceTypeId` | `null` | Filter by reference type |
| `includeSubtypes` | `true` | Include subtypes |
| `nodeClassMask` | `0` | Filter by node class (bitmask, 0 = all) |

### Depth Limits

| `maxDepth` | What happens |
|------------|--------------|
| `null` (default) | Uses the configured default (10, or whatever you set with `setDefaultBrowseMaxDepth()`) |
| `1` | Direct children only, no recursion |
| `-1` | Unlimited (capped at 256 internally) |
| Any value > 256 | Capped at 256 |

> **Watch out:** High `maxDepth` values (or `-1`) can hurt:
>
> - **Performance:** Each level sends one browse request per node. Thousands of nodes = thousands of round-trips. Can take minutes.
> - **Memory:** The whole tree lives in memory. Large address spaces will eat RAM.
> - **Server load:** Massive recursive browsing can overwhelm resource-constrained PLCs or embedded devices.
> - **Circular references:** OPC UA address spaces can have circular refs (e.g., TypeDefinition nodes pointing at each other). Cycle detection prevents infinite loops, but traversal may still visit a lot of nodes before resolving all cycles.
>
> Start with a small `maxDepth` and increase only when needed.

### Cycle Detection

The method keeps track of every NodeId it visits. If a node shows up again, it's included as a leaf (no children) to cut the recursion. This is how other OPC UA libraries handle it too (open62541, node-opcua, OPC Foundation .NET SDK).

### BrowseNode

Each tree node wraps a `ReferenceDescription` and holds its children:

```php
$node->getReference();    // ReferenceDescription — the original reference
$node->getNodeId();       // NodeId
$node->getDisplayName();  // LocalizedText
$node->getBrowseName();   // QualifiedName
$node->getNodeClass();    // NodeClass enum
$node->getChildren();     // BrowseNode[] — child nodes
$node->hasChildren();     // bool
```

### Printing a Tree

```php
function printTree(array $nodes, int $indent = 0): void
{
    foreach ($nodes as $node) {
        echo str_repeat('  ', $indent) . $node->getDisplayName() . "\n";
        printTree($node->getChildren(), $indent + 1);
    }
}

$tree = $client->browseRecursive(NodeId::numeric(0, 85), maxDepth: 3);
printTree($tree);
```

### Configuration Methods

| Method | What |
|--------|------|
| `setDefaultBrowseMaxDepth(int)` | Set the default maxDepth for `browseRecursive()`. Default: 10, -1 for unlimited. |
| `getDefaultBrowseMaxDepth(): int` | Get the current default. |

## Path Resolution

Instead of browsing node by node, you can resolve a human-readable path to a NodeId with `resolveNodeId()`:

```php
$nodeId = $client->resolveNodeId('/Objects/Server/ServerStatus/State');

// then use it
$dataValue = $client->read($nodeId);
```

Under the hood this calls `TranslateBrowsePathsToNodeIds` — a single request, much faster than manual browsing.

### Path Format

- Segments separated by `/`
- Leading `/` is optional (`/Objects/Server` and `Objects/Server` are the same)
- Default starting node is Root (`ns=0;i=84`)
- For non-zero namespaces: `ns:Name` format — `"Objects/2:MyPLC/2:Temperature"`

```php
// Simple
$nodeId = $client->resolveNodeId('/Objects/Server');

// With namespaced segments
$nodeId = $client->resolveNodeId('/Objects/2:MyPLC/2:Temperature');

// Custom starting node (start from Objects instead of Root)
$nodeId = $client->resolveNodeId('Server', NodeId::numeric(0, 85));
```

### translateBrowsePaths (Advanced)

For full control over `TranslateBrowsePathsToNodeIds`:

```php
use Gianfriaur\OpcuaPhpClient\Types\QualifiedName;
use Gianfriaur\OpcuaPhpClient\Types\StatusCode;

$results = $client->translateBrowsePaths([
    [
        'startingNodeId' => NodeId::numeric(0, 85), // Objects
        'relativePath' => [
            ['targetName' => new QualifiedName(0, 'Server')],
            ['targetName' => new QualifiedName(0, 'ServerStatus')],
        ],
    ],
]);

if (StatusCode::isGood($results[0]['statusCode'])) {
    $targetNodeId = $results[0]['targets'][0]['targetId'];
}
```

Each path element supports:

| Field | Default | What |
|-------|---------|------|
| `targetName` | (required) | `QualifiedName` of the target |
| `referenceTypeId` | HierarchicalReferences | Reference type to follow |
| `isInverse` | `false` | Follow inverse references |
| `includeSubtypes` | `true` | Include subtypes |

Multiple paths can be resolved in one request.
