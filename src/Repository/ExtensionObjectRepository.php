<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Repository;

use Gianfriaur\OpcuaPhpClient\Encoding\ExtensionObjectCodec;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;

/**
 * Registry for ExtensionObjectCodec instances, keyed by their OPC UA type NodeId.
 */
class ExtensionObjectRepository
{
    /** @var array<string, ExtensionObjectCodec> */
    private array $codecs = [];

    /**
     * Registers a codec for the given extension object type NodeId.
     *
     * @param NodeId $typeId
     * @param class-string<ExtensionObjectCodec>|ExtensionObjectCodec $codec
     * @return void
     */
    public function register(NodeId $typeId, string|ExtensionObjectCodec $codec): void
    {
        if (is_string($codec)) {
            $codec = new $codec();
        }

        $this->codecs[$this->key($typeId)] = $codec;
    }

    /**
     * Removes the codec registered for the given type NodeId.
     *
     * @param NodeId $typeId
     * @return void
     */
    public function unregister(NodeId $typeId): void
    {
        unset($this->codecs[$this->key($typeId)]);
    }

    /**
     * Returns the codec registered for the given type NodeId, or null if not found.
     *
     * @param NodeId $typeId
     * @return ExtensionObjectCodec|null
     */
    public function get(NodeId $typeId): ?ExtensionObjectCodec
    {
        return $this->codecs[$this->key($typeId)] ?? null;
    }

    /**
     * Checks whether a codec is registered for the given type NodeId.
     *
     * @param NodeId $typeId
     * @return bool
     */
    public function has(NodeId $typeId): bool
    {
        return isset($this->codecs[$this->key($typeId)]);
    }

    /**
     * Removes all registered codecs.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->codecs = [];
    }

    /**
     * @param NodeId $nodeId
     * @return string
     */
    private function key(NodeId $nodeId): string
    {
        return $nodeId->__toString();
    }
}
