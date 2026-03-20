<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Builder;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;
use Gianfriaur\OpcuaPhpClient\Types\AttributeId;
use Gianfriaur\OpcuaPhpClient\Types\DataValue;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;

/**
 * Fluent builder for multi-node read operations.
 *
 * @see OpcUaClientInterface::readMulti()
 */
class ReadMultiBuilder
{
    /** @var array<array{nodeId: NodeId|string, attributeId?: int}> */
    private array $items = [];

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
        $this->items[] = ['nodeId' => $nodeId];
        return $this;
    }

    /**
     * @return $this
     */
    public function value(): self
    {
        return $this->attribute(AttributeId::Value);
    }

    /**
     * @return $this
     */
    public function displayName(): self
    {
        return $this->attribute(AttributeId::DisplayName);
    }

    /**
     * @return $this
     */
    public function browseName(): self
    {
        return $this->attribute(AttributeId::BrowseName);
    }

    /**
     * @return $this
     */
    public function nodeClass(): self
    {
        return $this->attribute(AttributeId::NodeClass);
    }

    /**
     * @return $this
     */
    public function description(): self
    {
        return $this->attribute(AttributeId::Description);
    }

    /**
     * @return $this
     */
    public function dataType(): self
    {
        return $this->attribute(AttributeId::DataType);
    }

    /**
     * @param int $attributeId
     * @return $this
     */
    public function attribute(int $attributeId): self
    {
        if (!empty($this->items)) {
            $this->items[array_key_last($this->items)]['attributeId'] = $attributeId;
        }
        return $this;
    }

    /**
     * @return DataValue[]
     */
    public function execute(): array
    {
        return $this->client->readMulti($this->items);
    }
}
