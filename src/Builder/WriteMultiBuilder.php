<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Builder;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;
use Gianfriaur\OpcuaPhpClient\Types\BuiltinType;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;

/**
 * Fluent builder for multi-node write operations.
 *
 * @see OpcUaClientInterface::writeMulti()
 */
class WriteMultiBuilder
{
    /** @var array<array{nodeId: NodeId|string, value: mixed, type: BuiltinType}> */
    private array $items = [];
    private NodeId|string|null $currentNodeId = null;

    /**
     * @param OpcUaClientInterface $client
     */
    public function __construct(
        private readonly OpcUaClientInterface $client,
    )
    {
    }

    /**
     * @param NodeId|string $nodeId
     * @return $this
     */
    public function node(NodeId|string $nodeId): self
    {
        $this->currentNodeId = $nodeId;
        return $this;
    }

    /**
     * @param mixed $value
     * @param BuiltinType $type
     * @return $this
     */
    public function typed(mixed $value, BuiltinType $type): self
    {
        $this->items[] = ['nodeId' => $this->currentNodeId, 'value' => $value, 'type' => $type];
        return $this;
    }

    /** @return $this */
    public function boolean(bool $value): self { return $this->typed($value, BuiltinType::Boolean); }

    /** @return $this */
    public function sbyte(int $value): self { return $this->typed($value, BuiltinType::SByte); }

    /** @return $this */
    public function byte(int $value): self { return $this->typed($value, BuiltinType::Byte); }

    /** @return $this */
    public function int16(int $value): self { return $this->typed($value, BuiltinType::Int16); }

    /** @return $this */
    public function uint16(int $value): self { return $this->typed($value, BuiltinType::UInt16); }

    /** @return $this */
    public function int32(int $value): self { return $this->typed($value, BuiltinType::Int32); }

    /** @return $this */
    public function uint32(int $value): self { return $this->typed($value, BuiltinType::UInt32); }

    /** @return $this */
    public function int64(int $value): self { return $this->typed($value, BuiltinType::Int64); }

    /** @return $this */
    public function uint64(int $value): self { return $this->typed($value, BuiltinType::UInt64); }

    /** @return $this */
    public function float(float $value): self { return $this->typed($value, BuiltinType::Float); }

    /** @return $this */
    public function double(float $value): self { return $this->typed($value, BuiltinType::Double); }

    /** @return $this */
    public function string(string $value): self { return $this->typed($value, BuiltinType::String); }

    /**
     * @return int[]
     */
    public function execute(): array
    {
        return $this->client->writeMulti($this->items);
    }
}
