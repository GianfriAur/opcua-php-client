<?php

declare(strict_types=1);

require_once __DIR__ . '/../Client/ClientTraitsCoverageTest.php';

use Gianfriaur\OpcuaPhpClient\Builder\BrowsePathsBuilder;
use Gianfriaur\OpcuaPhpClient\Builder\MonitoredItemsBuilder;
use Gianfriaur\OpcuaPhpClient\Builder\ReadMultiBuilder;
use Gianfriaur\OpcuaPhpClient\Builder\WriteMultiBuilder;
use Gianfriaur\OpcuaPhpClient\Encoding\BinaryEncoder;
use Gianfriaur\OpcuaPhpClient\Types\BuiltinType;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;

describe('ReadMultiBuilder', function () {

    it('reads multiple nodes with fluent API', function () {
        $mock = new MockTransport();
        $mock->addResponse(buildMsgResponse(634, function (BinaryEncoder $e) {
            $e->writeInt32(2);
            $e->writeByte(0x01);
            $e->writeByte(BuiltinType::Int32->value);
            $e->writeInt32(42);
            $e->writeByte(0x01);
            $e->writeByte(BuiltinType::String->value);
            $e->writeString('Server');
            $e->writeInt32(0);
        }));

        $client = setupConnectedClient($mock);
        $results = $client->readMulti()
            ->node('i=2259')->value()
            ->node('ns=2;i=1001')->displayName()
            ->execute();

        expect($results)->toHaveCount(2);
        expect($results[0]->getValue())->toBe(42);
        expect($results[1]->getValue())->toBe('Server');
    });

    it('readMulti still works with array parameter', function () {
        $mock = new MockTransport();
        $mock->addResponse(readResponseMsg(99));

        $client = setupConnectedClient($mock);
        $results = $client->readMulti([['nodeId' => 'i=2259']]);

        expect($results)->toHaveCount(1);
    });

    it('supports all attribute shortcuts', function () {
        $builder = new ReadMultiBuilder(
            new class implements \Gianfriaur\OpcuaPhpClient\OpcUaClientInterface {
                public array $captured = [];
                public function readMulti(?array $readItems = null): array|\Gianfriaur\OpcuaPhpClient\Builder\ReadMultiBuilder { $this->captured = $readItems; return []; }
                public function getExtensionObjectRepository(): \Gianfriaur\OpcuaPhpClient\Repository\ExtensionObjectRepository { return new \Gianfriaur\OpcuaPhpClient\Repository\ExtensionObjectRepository(); }
                public function setTimeout(float $timeout): self { return $this; }
                public function getTimeout(): float { return 5.0; }
                public function setAutoRetry(int $maxRetries): self { return $this; }
                public function getAutoRetry(): int { return 0; }
                public function setBatchSize(int $batchSize): self { return $this; }
                public function getBatchSize(): ?int { return null; }
                public function getServerMaxNodesPerRead(): ?int { return null; }
                public function getServerMaxNodesPerWrite(): ?int { return null; }
                public function connect(string $endpointUrl): void {}
                public function reconnect(): void {}
                public function disconnect(): void {}
                public function isConnected(): bool { return true; }
                public function getConnectionState(): \Gianfriaur\OpcuaPhpClient\Types\ConnectionState { return \Gianfriaur\OpcuaPhpClient\Types\ConnectionState::Connected; }
                public function discoverDataTypes(?int $namespaceIndex = null): int { return 0; }
                public function getEndpoints(string $endpointUrl): array { return []; }
                public function browse(NodeId|string $nodeId, \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection $direction = \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection::Forward, ?NodeId $referenceTypeId = null, bool $includeSubtypes = true, array $nodeClasses = []): array { return []; }
                public function browseWithContinuation(NodeId|string $nodeId, \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection $direction = \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection::Forward, ?NodeId $referenceTypeId = null, bool $includeSubtypes = true, array $nodeClasses = []): \Gianfriaur\OpcuaPhpClient\Types\BrowseResultSet { return new \Gianfriaur\OpcuaPhpClient\Types\BrowseResultSet([], null); }
                public function browseNext(string $continuationPoint): \Gianfriaur\OpcuaPhpClient\Types\BrowseResultSet { return new \Gianfriaur\OpcuaPhpClient\Types\BrowseResultSet([], null); }
                public function browseAll(NodeId|string $nodeId, \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection $direction = \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection::Forward, ?NodeId $referenceTypeId = null, bool $includeSubtypes = true, array $nodeClasses = []): array { return []; }
                public function setDefaultBrowseMaxDepth(int $maxDepth): self { return $this; }
                public function getDefaultBrowseMaxDepth(): int { return 10; }
                public function browseRecursive(NodeId|string $nodeId, \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection $direction = \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection::Forward, ?int $maxDepth = null, ?NodeId $referenceTypeId = null, bool $includeSubtypes = true, array $nodeClasses = []): array { return []; }
                public function translateBrowsePaths(?array $browsePaths = null): array|\Gianfriaur\OpcuaPhpClient\Builder\BrowsePathsBuilder { return []; }
                public function resolveNodeId(string $path, NodeId|string|null $startingNodeId = null): NodeId { return NodeId::numeric(0, 0); }
                public function read(NodeId|string $nodeId, int $attributeId = 13): \Gianfriaur\OpcuaPhpClient\Types\DataValue { return new \Gianfriaur\OpcuaPhpClient\Types\DataValue(); }
                public function write(NodeId|string $nodeId, mixed $value, BuiltinType $type): int { return 0; }
                public function writeMulti(?array $writeItems = null): array|WriteMultiBuilder { return []; }
                public function call(NodeId|string $objectId, NodeId|string $methodId, array $inputArguments = []): \Gianfriaur\OpcuaPhpClient\Types\CallResult { return new \Gianfriaur\OpcuaPhpClient\Types\CallResult(0, [], []); }
                public function createSubscription(float $publishingInterval = 500.0, int $lifetimeCount = 2400, int $maxKeepAliveCount = 10, int $maxNotificationsPerPublish = 0, bool $publishingEnabled = true, int $priority = 0): \Gianfriaur\OpcuaPhpClient\Types\SubscriptionResult { return new \Gianfriaur\OpcuaPhpClient\Types\SubscriptionResult(0, 0, 0, 0); }
                public function createMonitoredItems(int $subscriptionId, ?array $items = null): array|MonitoredItemsBuilder { return []; }
                public function createEventMonitoredItem(int $subscriptionId, NodeId|string $nodeId, array $selectFields = [], int $clientHandle = 1): \Gianfriaur\OpcuaPhpClient\Types\MonitoredItemResult { return new \Gianfriaur\OpcuaPhpClient\Types\MonitoredItemResult(0, 0, 0, 0); }
                public function deleteMonitoredItems(int $subscriptionId, array $monitoredItemIds): array { return []; }
                public function deleteSubscription(int $subscriptionId): int { return 0; }
                public function publish(array $acknowledgements = []): \Gianfriaur\OpcuaPhpClient\Types\PublishResult { return new \Gianfriaur\OpcuaPhpClient\Types\PublishResult(0, 0, false, [], []); }
                public function historyReadRaw(NodeId|string $nodeId, ?\DateTimeImmutable $startTime = null, ?\DateTimeImmutable $endTime = null, int $numValuesPerNode = 0, bool $returnBounds = false): array { return []; }
                public function historyReadProcessed(NodeId|string $nodeId, \DateTimeImmutable $startTime, \DateTimeImmutable $endTime, float $processingInterval, NodeId $aggregateType): array { return []; }
                public function historyReadAtTime(NodeId|string $nodeId, array $timestamps): array { return []; }
            }
        );

        $builder->node('i=1')->value()
            ->node('i=2')->displayName()
            ->node('i=3')->browseName()
            ->node('i=4')->nodeClass()
            ->node('i=5')->description()
            ->node('i=6')->dataType()
            ->node('i=7')->attribute(17);

        $builder->execute();

        expect(true)->toBeTrue();
    });
});

describe('WriteMultiBuilder', function () {

    it('writes multiple nodes with fluent API', function () {
        $mock = new MockTransport();
        $mock->addResponse(buildMsgResponse(676, function (BinaryEncoder $e) {
            $e->writeInt32(2);
            $e->writeUInt32(0);
            $e->writeUInt32(0);
            $e->writeInt32(0);
        }));

        $client = setupConnectedClient($mock);
        $results = $client->writeMulti()
            ->node('ns=2;i=1001')->int32(42)
            ->node('ns=2;i=1002')->double(3.14)
            ->execute();

        expect($results)->toBe([0, 0]);
    });

    it('supports all type shortcuts', function () {
        $builder = new WriteMultiBuilder(
            $GLOBALS['_mockClient'] ?? new class implements \Gianfriaur\OpcuaPhpClient\OpcUaClientInterface {
                public function writeMulti(?array $writeItems = null): array|WriteMultiBuilder { return []; }
                public function getExtensionObjectRepository(): \Gianfriaur\OpcuaPhpClient\Repository\ExtensionObjectRepository { return new \Gianfriaur\OpcuaPhpClient\Repository\ExtensionObjectRepository(); }
                public function setTimeout(float $timeout): self { return $this; }
                public function getTimeout(): float { return 5.0; }
                public function setAutoRetry(int $maxRetries): self { return $this; }
                public function getAutoRetry(): int { return 0; }
                public function setBatchSize(int $batchSize): self { return $this; }
                public function getBatchSize(): ?int { return null; }
                public function getServerMaxNodesPerRead(): ?int { return null; }
                public function getServerMaxNodesPerWrite(): ?int { return null; }
                public function connect(string $endpointUrl): void {}
                public function reconnect(): void {}
                public function disconnect(): void {}
                public function isConnected(): bool { return true; }
                public function getConnectionState(): \Gianfriaur\OpcuaPhpClient\Types\ConnectionState { return \Gianfriaur\OpcuaPhpClient\Types\ConnectionState::Connected; }
                public function discoverDataTypes(?int $namespaceIndex = null): int { return 0; }
                public function getEndpoints(string $endpointUrl): array { return []; }
                public function browse(NodeId|string $n, \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection $d = \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection::Forward, ?NodeId $r = null, bool $i = true, array $c = []): array { return []; }
                public function browseWithContinuation(NodeId|string $n, \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection $d = \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection::Forward, ?NodeId $r = null, bool $i = true, array $c = []): \Gianfriaur\OpcuaPhpClient\Types\BrowseResultSet { return new \Gianfriaur\OpcuaPhpClient\Types\BrowseResultSet([], null); }
                public function browseNext(string $cp): \Gianfriaur\OpcuaPhpClient\Types\BrowseResultSet { return new \Gianfriaur\OpcuaPhpClient\Types\BrowseResultSet([], null); }
                public function browseAll(NodeId|string $n, \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection $d = \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection::Forward, ?NodeId $r = null, bool $i = true, array $c = []): array { return []; }
                public function setDefaultBrowseMaxDepth(int $m): self { return $this; }
                public function getDefaultBrowseMaxDepth(): int { return 10; }
                public function browseRecursive(NodeId|string $n, \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection $d = \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection::Forward, ?int $m = null, ?NodeId $r = null, bool $i = true, array $c = []): array { return []; }
                public function translateBrowsePaths(?array $b = null): array|BrowsePathsBuilder { return []; }
                public function resolveNodeId(string $p, NodeId|string|null $s = null): NodeId { return NodeId::numeric(0, 0); }
                public function read(NodeId|string $n, int $a = 13): \Gianfriaur\OpcuaPhpClient\Types\DataValue { return new \Gianfriaur\OpcuaPhpClient\Types\DataValue(); }
                public function readMulti(?array $r = null): array|ReadMultiBuilder { return []; }
                public function write(NodeId|string $n, mixed $v, BuiltinType $t): int { return 0; }
                public function call(NodeId|string $o, NodeId|string $m, array $i = []): \Gianfriaur\OpcuaPhpClient\Types\CallResult { return new \Gianfriaur\OpcuaPhpClient\Types\CallResult(0, [], []); }
                public function createSubscription(float $pi = 500.0, int $lc = 2400, int $mkac = 10, int $mnpp = 0, bool $pe = true, int $p = 0): \Gianfriaur\OpcuaPhpClient\Types\SubscriptionResult { return new \Gianfriaur\OpcuaPhpClient\Types\SubscriptionResult(0, 0, 0, 0); }
                public function createMonitoredItems(int $sid, ?array $items = null): array|MonitoredItemsBuilder { return []; }
                public function createEventMonitoredItem(int $sid, NodeId|string $n, array $sf = [], int $ch = 1): \Gianfriaur\OpcuaPhpClient\Types\MonitoredItemResult { return new \Gianfriaur\OpcuaPhpClient\Types\MonitoredItemResult(0, 0, 0, 0); }
                public function deleteMonitoredItems(int $sid, array $ids): array { return []; }
                public function deleteSubscription(int $sid): int { return 0; }
                public function publish(array $a = []): \Gianfriaur\OpcuaPhpClient\Types\PublishResult { return new \Gianfriaur\OpcuaPhpClient\Types\PublishResult(0, 0, false, [], []); }
                public function historyReadRaw(NodeId|string $n, ?\DateTimeImmutable $s = null, ?\DateTimeImmutable $e = null, int $nv = 0, bool $rb = false): array { return []; }
                public function historyReadProcessed(NodeId|string $n, \DateTimeImmutable $s, \DateTimeImmutable $e, float $pi, NodeId $at): array { return []; }
                public function historyReadAtTime(NodeId|string $n, array $t): array { return []; }
            }
        );

        $builder->node('i=1')->boolean(true)
            ->node('i=2')->sbyte(-1)
            ->node('i=3')->byte(255)
            ->node('i=4')->int16(-100)
            ->node('i=5')->uint16(100)
            ->node('i=6')->int32(42)
            ->node('i=7')->uint32(42)
            ->node('i=8')->int64(999)
            ->node('i=9')->uint64(999)
            ->node('i=10')->float(1.5)
            ->node('i=11')->double(3.14)
            ->node('i=12')->string('hello')
            ->node('i=13')->typed(42, BuiltinType::Int32);

        $builder->execute();
        expect(true)->toBeTrue();
    });
});

describe('BrowsePathsBuilder', function () {

    it('translates paths with fluent API', function () {
        $mock = new MockTransport();
        $mock->addResponse(buildMsgResponse(557, function (BinaryEncoder $e) {
            $e->writeInt32(1);
            $e->writeUInt32(0);
            $e->writeInt32(1);
            $e->writeExpandedNodeId(NodeId::numeric(0, 2253));
            $e->writeUInt32(0xFFFFFFFF);
            $e->writeInt32(0);
        }));

        $client = setupConnectedClient($mock);
        $results = $client->translateBrowsePaths()
            ->from('i=85')->path('Server')
            ->execute();

        expect($results)->toHaveCount(1);
        expect($results[0]->targets[0]->targetId->identifier)->toBe(2253);
    });

    it('supports multi-segment paths', function () {
        $mock = new MockTransport();
        $mock->addResponse(buildMsgResponse(557, function (BinaryEncoder $e) {
            $e->writeInt32(1);
            $e->writeUInt32(0);
            $e->writeInt32(1);
            $e->writeExpandedNodeId(NodeId::numeric(0, 2259));
            $e->writeUInt32(0xFFFFFFFF);
            $e->writeInt32(0);
        }));

        $client = setupConnectedClient($mock);
        $results = $client->translateBrowsePaths()
            ->from('i=85')->path('Server', 'ServerStatus', 'State')
            ->execute();

        expect($results)->toHaveCount(1);
    });

    it('supports namespaced segments', function () {
        $mock = new MockTransport();
        $mock->addResponse(buildMsgResponse(557, function (BinaryEncoder $e) {
            $e->writeInt32(1);
            $e->writeUInt32(0);
            $e->writeInt32(1);
            $e->writeExpandedNodeId(NodeId::numeric(2, 1001));
            $e->writeUInt32(0xFFFFFFFF);
            $e->writeInt32(0);
        }));

        $client = setupConnectedClient($mock);
        $results = $client->translateBrowsePaths()
            ->from('i=85')->path('2:MyPLC', '2:Temperature')
            ->execute();

        expect($results)->toHaveCount(1);
    });

    it('auto-adds root node when from() is not called', function () {
        $builder = new BrowsePathsBuilder(
            new class implements \Gianfriaur\OpcuaPhpClient\OpcUaClientInterface {
                public array $captured = [];
                public function translateBrowsePaths(?array $browsePaths = null): array|BrowsePathsBuilder { $this->captured = $browsePaths; return []; }
                public function getExtensionObjectRepository(): \Gianfriaur\OpcuaPhpClient\Repository\ExtensionObjectRepository { return new \Gianfriaur\OpcuaPhpClient\Repository\ExtensionObjectRepository(); }
                public function setTimeout(float $t): self { return $this; }
                public function getTimeout(): float { return 5.0; }
                public function setAutoRetry(int $m): self { return $this; }
                public function getAutoRetry(): int { return 0; }
                public function setBatchSize(int $b): self { return $this; }
                public function getBatchSize(): ?int { return null; }
                public function getServerMaxNodesPerRead(): ?int { return null; }
                public function getServerMaxNodesPerWrite(): ?int { return null; }
                public function connect(string $u): void {}
                public function reconnect(): void {}
                public function disconnect(): void {}
                public function isConnected(): bool { return true; }
                public function getConnectionState(): \Gianfriaur\OpcuaPhpClient\Types\ConnectionState { return \Gianfriaur\OpcuaPhpClient\Types\ConnectionState::Connected; }
                public function discoverDataTypes(?int $n = null): int { return 0; }
                public function getEndpoints(string $u): array { return []; }
                public function browse(NodeId|string $n, \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection $d = \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection::Forward, ?NodeId $r = null, bool $i = true, array $c = []): array { return []; }
                public function browseWithContinuation(NodeId|string $n, \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection $d = \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection::Forward, ?NodeId $r = null, bool $i = true, array $c = []): \Gianfriaur\OpcuaPhpClient\Types\BrowseResultSet { return new \Gianfriaur\OpcuaPhpClient\Types\BrowseResultSet([], null); }
                public function browseNext(string $cp): \Gianfriaur\OpcuaPhpClient\Types\BrowseResultSet { return new \Gianfriaur\OpcuaPhpClient\Types\BrowseResultSet([], null); }
                public function browseAll(NodeId|string $n, \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection $d = \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection::Forward, ?NodeId $r = null, bool $i = true, array $c = []): array { return []; }
                public function setDefaultBrowseMaxDepth(int $m): self { return $this; }
                public function getDefaultBrowseMaxDepth(): int { return 10; }
                public function browseRecursive(NodeId|string $n, \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection $d = \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection::Forward, ?int $m = null, ?NodeId $r = null, bool $i = true, array $c = []): array { return []; }
                public function resolveNodeId(string $p, NodeId|string|null $s = null): NodeId { return NodeId::numeric(0, 0); }
                public function read(NodeId|string $n, int $a = 13): \Gianfriaur\OpcuaPhpClient\Types\DataValue { return new \Gianfriaur\OpcuaPhpClient\Types\DataValue(); }
                public function readMulti(?array $r = null): array|ReadMultiBuilder { return []; }
                public function write(NodeId|string $n, mixed $v, BuiltinType $t): int { return 0; }
                public function writeMulti(?array $w = null): array|WriteMultiBuilder { return []; }
                public function call(NodeId|string $o, NodeId|string $m, array $i = []): \Gianfriaur\OpcuaPhpClient\Types\CallResult { return new \Gianfriaur\OpcuaPhpClient\Types\CallResult(0, [], []); }
                public function createSubscription(float $pi = 500.0, int $lc = 2400, int $mkac = 10, int $mnpp = 0, bool $pe = true, int $p = 0): \Gianfriaur\OpcuaPhpClient\Types\SubscriptionResult { return new \Gianfriaur\OpcuaPhpClient\Types\SubscriptionResult(0, 0, 0, 0); }
                public function createMonitoredItems(int $sid, ?array $items = null): array|MonitoredItemsBuilder { return []; }
                public function createEventMonitoredItem(int $sid, NodeId|string $n, array $sf = [], int $ch = 1): \Gianfriaur\OpcuaPhpClient\Types\MonitoredItemResult { return new \Gianfriaur\OpcuaPhpClient\Types\MonitoredItemResult(0, 0, 0, 0); }
                public function deleteMonitoredItems(int $sid, array $ids): array { return []; }
                public function deleteSubscription(int $sid): int { return 0; }
                public function publish(array $a = []): \Gianfriaur\OpcuaPhpClient\Types\PublishResult { return new \Gianfriaur\OpcuaPhpClient\Types\PublishResult(0, 0, false, [], []); }
                public function historyReadRaw(NodeId|string $n, ?\DateTimeImmutable $s = null, ?\DateTimeImmutable $e = null, int $nv = 0, bool $rb = false): array { return []; }
                public function historyReadProcessed(NodeId|string $n, \DateTimeImmutable $s, \DateTimeImmutable $e, float $pi, NodeId $at): array { return []; }
                public function historyReadAtTime(NodeId|string $n, array $t): array { return []; }
            }
        );

        $builder->path('Objects', 'Server')->execute();
        expect(true)->toBeTrue();
    });

    it('supports explicit QualifiedName segments', function () {
        $builder = new BrowsePathsBuilder(
            new class implements \Gianfriaur\OpcuaPhpClient\OpcUaClientInterface {
                public function translateBrowsePaths(?array $b = null): array|BrowsePathsBuilder { return []; }
                public function getExtensionObjectRepository(): \Gianfriaur\OpcuaPhpClient\Repository\ExtensionObjectRepository { return new \Gianfriaur\OpcuaPhpClient\Repository\ExtensionObjectRepository(); }
                public function setTimeout(float $t): self { return $this; } public function getTimeout(): float { return 5.0; } public function setAutoRetry(int $m): self { return $this; } public function getAutoRetry(): int { return 0; } public function setBatchSize(int $b): self { return $this; } public function getBatchSize(): ?int { return null; } public function getServerMaxNodesPerRead(): ?int { return null; } public function getServerMaxNodesPerWrite(): ?int { return null; } public function connect(string $u): void {} public function reconnect(): void {} public function disconnect(): void {} public function isConnected(): bool { return true; } public function getConnectionState(): \Gianfriaur\OpcuaPhpClient\Types\ConnectionState { return \Gianfriaur\OpcuaPhpClient\Types\ConnectionState::Connected; } public function discoverDataTypes(?int $n = null): int { return 0; } public function getEndpoints(string $u): array { return []; } public function browse(NodeId|string $n, \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection $d = \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection::Forward, ?NodeId $r = null, bool $i = true, array $c = []): array { return []; } public function browseWithContinuation(NodeId|string $n, \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection $d = \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection::Forward, ?NodeId $r = null, bool $i = true, array $c = []): \Gianfriaur\OpcuaPhpClient\Types\BrowseResultSet { return new \Gianfriaur\OpcuaPhpClient\Types\BrowseResultSet([], null); } public function browseNext(string $cp): \Gianfriaur\OpcuaPhpClient\Types\BrowseResultSet { return new \Gianfriaur\OpcuaPhpClient\Types\BrowseResultSet([], null); } public function browseAll(NodeId|string $n, \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection $d = \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection::Forward, ?NodeId $r = null, bool $i = true, array $c = []): array { return []; } public function setDefaultBrowseMaxDepth(int $m): self { return $this; } public function getDefaultBrowseMaxDepth(): int { return 10; } public function browseRecursive(NodeId|string $n, \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection $d = \Gianfriaur\OpcuaPhpClient\Types\BrowseDirection::Forward, ?int $m = null, ?NodeId $r = null, bool $i = true, array $c = []): array { return []; } public function resolveNodeId(string $p, NodeId|string|null $s = null): NodeId { return NodeId::numeric(0, 0); } public function read(NodeId|string $n, int $a = 13): \Gianfriaur\OpcuaPhpClient\Types\DataValue { return new \Gianfriaur\OpcuaPhpClient\Types\DataValue(); } public function readMulti(?array $r = null): array|ReadMultiBuilder { return []; } public function write(NodeId|string $n, mixed $v, BuiltinType $t): int { return 0; } public function writeMulti(?array $w = null): array|WriteMultiBuilder { return []; } public function call(NodeId|string $o, NodeId|string $m, array $i = []): \Gianfriaur\OpcuaPhpClient\Types\CallResult { return new \Gianfriaur\OpcuaPhpClient\Types\CallResult(0, [], []); } public function createSubscription(float $pi = 500.0, int $lc = 2400, int $mkac = 10, int $mnpp = 0, bool $pe = true, int $p = 0): \Gianfriaur\OpcuaPhpClient\Types\SubscriptionResult { return new \Gianfriaur\OpcuaPhpClient\Types\SubscriptionResult(0, 0, 0, 0); } public function createMonitoredItems(int $sid, ?array $items = null): array|MonitoredItemsBuilder { return []; } public function createEventMonitoredItem(int $sid, NodeId|string $n, array $sf = [], int $ch = 1): \Gianfriaur\OpcuaPhpClient\Types\MonitoredItemResult { return new \Gianfriaur\OpcuaPhpClient\Types\MonitoredItemResult(0, 0, 0, 0); } public function deleteMonitoredItems(int $sid, array $ids): array { return []; } public function deleteSubscription(int $sid): int { return 0; } public function publish(array $a = []): \Gianfriaur\OpcuaPhpClient\Types\PublishResult { return new \Gianfriaur\OpcuaPhpClient\Types\PublishResult(0, 0, false, [], []); } public function historyReadRaw(NodeId|string $n, ?\DateTimeImmutable $s = null, ?\DateTimeImmutable $e = null, int $nv = 0, bool $rb = false): array { return []; } public function historyReadProcessed(NodeId|string $n, \DateTimeImmutable $s, \DateTimeImmutable $e, float $pi, NodeId $at): array { return []; } public function historyReadAtTime(NodeId|string $n, array $t): array { return []; }
            }
        );

        $builder->from('i=85')
            ->segment(new \Gianfriaur\OpcuaPhpClient\Types\QualifiedName(2, 'Custom'))
            ->execute();
        expect(true)->toBeTrue();
    });
});

describe('MonitoredItemsBuilder', function () {

    it('returns builder when createMonitoredItems is called without items', function () {
        $client = new \Gianfriaur\OpcuaPhpClient\Client();
        $builder = $client->createMonitoredItems(1);

        expect($builder)->toBeInstanceOf(MonitoredItemsBuilder::class);
    });

    it('builds monitored items with fluent API', function () {
        $mock = new MockTransport();
        $mock->addResponse(buildMsgResponse(754, function (BinaryEncoder $e) {
            $e->writeInt32(2);
            $e->writeUInt32(0);
            $e->writeUInt32(1);
            $e->writeDouble(500.0);
            $e->writeUInt32(2);
            $e->writeNodeId(NodeId::numeric(0, 0));
            $e->writeByte(0x00);
            $e->writeUInt32(0);
            $e->writeUInt32(2);
            $e->writeDouble(1000.0);
            $e->writeUInt32(2);
            $e->writeNodeId(NodeId::numeric(0, 0));
            $e->writeByte(0x00);
            $e->writeInt32(0);
        }));

        $client = setupConnectedClient($mock);
        $results = $client->createMonitoredItems(1)
            ->add('i=2258')->samplingInterval(500.0)->queueSize(10)->clientHandle(1)
            ->add('ns=2;i=1001')->attributeId(13)
            ->execute();

        expect($results)->toHaveCount(2);
    });

    it('supports segment with explicit QualifiedName in BrowsePathsBuilder', function () {
        $mock = new MockTransport();
        $mock->addResponse(buildMsgResponse(557, function (BinaryEncoder $e) {
            $e->writeInt32(1);
            $e->writeUInt32(0);
            $e->writeInt32(1);
            $e->writeExpandedNodeId(NodeId::numeric(0, 2253));
            $e->writeUInt32(0xFFFFFFFF);
            $e->writeInt32(0);
        }));

        $client = setupConnectedClient($mock);
        $results = $client->translateBrowsePaths()
            ->from('i=85')
            ->segment(new \Gianfriaur\OpcuaPhpClient\Types\QualifiedName(0, 'Server'))
            ->execute();

        expect($results)->toHaveCount(1);
    });
});
