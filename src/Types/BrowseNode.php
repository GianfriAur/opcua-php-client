<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

class BrowseNode
{
    /** @var BrowseNode[] */
    private array $children = [];

    public function __construct(
        public readonly ReferenceDescription $reference,
    )
    {
    }

    /** @deprecated Access the public property directly instead. Use ->reference instead. */
    public function getReference(): ReferenceDescription
    {
        return $this->reference;
    }

    /** @deprecated Access the public property directly instead. Use ->reference->nodeId instead. */
    public function getNodeId(): NodeId
    {
        return $this->reference->getNodeId();
    }

    /** @deprecated Access the public property directly instead. Use ->reference->displayName instead. */
    public function getDisplayName(): LocalizedText
    {
        return $this->reference->getDisplayName();
    }

    /** @deprecated Access the public property directly instead. Use ->reference->browseName instead. */
    public function getBrowseName(): QualifiedName
    {
        return $this->reference->getBrowseName();
    }

    /** @deprecated Access the public property directly instead. Use ->reference->nodeClass instead. */
    public function getNodeClass(): NodeClass
    {
        return $this->reference->getNodeClass();
    }

    /**
     * @return BrowseNode[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    public function addChild(BrowseNode $child): void
    {
        $this->children[] = $child;
    }

    public function hasChildren(): bool
    {
        return count($this->children) > 0;
    }
}
