<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Builder;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;
use Gianfriaur\OpcuaPhpClient\Types\BrowsePathResult;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;
use Gianfriaur\OpcuaPhpClient\Types\QualifiedName;

/**
 * Fluent builder for translating browse paths to NodeIds.
 *
 * @see OpcUaClientInterface::translateBrowsePaths()
 */
class BrowsePathsBuilder
{
    /** @var array<array{startingNodeId: NodeId|string, relativePath: array<array{targetName: QualifiedName}>}> */
    private array $paths = [];

    /**
     * @param OpcUaClientInterface $client
     */
    public function __construct(
        private readonly OpcUaClientInterface $client,
    )
    {
    }

    /**
     * @param NodeId|string $startingNodeId
     * @return $this
     */
    public function from(NodeId|string $startingNodeId): self
    {
        $this->paths[] = ['startingNodeId' => $startingNodeId, 'relativePath' => []];
        return $this;
    }

    /**
     * @param string ...$segments
     * @return $this
     */
    public function path(string ...$segments): self
    {
        if (empty($this->paths)) {
            $this->from(NodeId::numeric(0, 84));
        }

        $idx = array_key_last($this->paths);
        foreach ($segments as $segment) {
            $this->paths[$idx]['relativePath'][] = ['targetName' => self::parseSegment($segment)];
        }
        return $this;
    }

    /**
     * @param QualifiedName $name
     * @return $this
     */
    public function segment(QualifiedName $name): self
    {
        if (empty($this->paths)) {
            $this->from(NodeId::numeric(0, 84));
        }

        $idx = array_key_last($this->paths);
        $this->paths[$idx]['relativePath'][] = ['targetName' => $name];
        return $this;
    }

    /**
     * @return BrowsePathResult[]
     */
    public function execute(): array
    {
        return $this->client->translateBrowsePaths($this->paths);
    }

    private static function parseSegment(string $segment): QualifiedName
    {
        if (str_contains($segment, ':')) {
            $parts = explode(':', $segment, 2);
            if (ctype_digit($parts[0])) {
                return new QualifiedName((int)$parts[0], $parts[1]);
            }
        }
        return new QualifiedName(0, $segment);
    }
}
