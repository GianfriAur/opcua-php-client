<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

/**
 * Represents a node in a hierarchical browse tree, wrapping a ReferenceDescription with child nodes.
 */
class BrowseNode
{
    /** @var BrowseNode[] */
    private array $children = [];

    /**
     * @param ReferenceDescription $reference
     */
    public function __construct(
        public readonly ReferenceDescription $reference,
    )
    {
    }

    /**
     * @deprecated Access the public property directly instead. Use ->reference instead.
     * @return ReferenceDescription
     * @see BrowseNode::$reference
     */
    public function getReference(): ReferenceDescription
    {
        return $this->reference;
    }

    /**
     * @deprecated Access the public property directly instead. Use ->reference->nodeId instead.
     * @return NodeId
     * @see ReferenceDescription::$nodeId
     */
    public function getNodeId(): NodeId
    {
        return $this->reference->getNodeId();
    }

    /**
     * @deprecated Access the public property directly instead. Use ->reference->displayName instead.
     * @return LocalizedText
     * @see ReferenceDescription::$displayName
     */
    public function getDisplayName(): LocalizedText
    {
        return $this->reference->getDisplayName();
    }

    /**
     * @deprecated Access the public property directly instead. Use ->reference->browseName instead.
     * @return QualifiedName
     * @see ReferenceDescription::$browseName
     */
    public function getBrowseName(): QualifiedName
    {
        return $this->reference->getBrowseName();
    }

    /**
     * @deprecated Access the public property directly instead. Use ->reference->nodeClass instead.
     * @return NodeClass
     * @see ReferenceDescription::$nodeClass
     */
    public function getNodeClass(): NodeClass
    {
        return $this->reference->getNodeClass();
    }

    /**
     * Returns the child nodes of this browse node.
     *
     * @return BrowseNode[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * Adds a child node to this browse node.
     *
     * @param BrowseNode $child
     * @return void
     */
    public function addChild(BrowseNode $child): void
    {
        $this->children[] = $child;
    }

    /**
     * Checks whether this browse node has any children.
     *
     * @return bool
     */
    public function hasChildren(): bool
    {
        return count($this->children) > 0;
    }
}
