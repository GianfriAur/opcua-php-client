<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Builder;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;
use Gianfriaur\OpcuaPhpClient\Types\MonitoredItemResult;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;

/**
 * Fluent builder for creating monitored items within a subscription.
 *
 * @see OpcUaClientInterface::createMonitoredItems()
 */
class MonitoredItemsBuilder
{
    /** @var array<array{nodeId: NodeId|string, attributeId?: int, samplingInterval?: float, queueSize?: int, clientHandle?: int, monitoringMode?: int}> */
    private array $items = [];

    /**
     * @param OpcUaClientInterface $client
     * @param int $subscriptionId
     */
    public function __construct(
        private readonly OpcUaClientInterface $client,
        private readonly int                  $subscriptionId,
    )
    {
    }

    /**
     * @param NodeId|string $nodeId
     * @return $this
     */
    public function add(NodeId|string $nodeId): self
    {
        $this->items[] = ['nodeId' => $nodeId];
        return $this;
    }

    /**
     * @param float $ms
     * @return $this
     */
    public function samplingInterval(float $ms): self
    {
        if (!empty($this->items)) {
            $this->items[array_key_last($this->items)]['samplingInterval'] = $ms;
        }
        return $this;
    }

    /**
     * @param int $size
     * @return $this
     */
    public function queueSize(int $size): self
    {
        if (!empty($this->items)) {
            $this->items[array_key_last($this->items)]['queueSize'] = $size;
        }
        return $this;
    }

    /**
     * @param int $handle
     * @return $this
     */
    public function clientHandle(int $handle): self
    {
        if (!empty($this->items)) {
            $this->items[array_key_last($this->items)]['clientHandle'] = $handle;
        }
        return $this;
    }

    /**
     * @param int $attributeId
     * @return $this
     */
    public function attributeId(int $attributeId): self
    {
        if (!empty($this->items)) {
            $this->items[array_key_last($this->items)]['attributeId'] = $attributeId;
        }
        return $this;
    }

    /**
     * @return MonitoredItemResult[]
     */
    public function execute(): array
    {
        return $this->client->createMonitoredItems($this->subscriptionId, $this->items);
    }
}
