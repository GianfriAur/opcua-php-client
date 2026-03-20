<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

readonly class ReferenceDescription
{
    /**
     * @param NodeId $referenceTypeId
     * @param bool $isForward
     * @param NodeId $nodeId
     * @param QualifiedName $browseName
     * @param LocalizedText $displayName
     * @param NodeClass $nodeClass
     * @param ?NodeId $typeDefinition
     */
    public function __construct(
        public NodeId        $referenceTypeId,
        public bool          $isForward,
        public NodeId        $nodeId,
        public QualifiedName $browseName,
        public LocalizedText $displayName,
        public NodeClass     $nodeClass,
        public ?NodeId       $typeDefinition = null,
    )
    {
    }

    /** @deprecated Access the public property directly instead. Use ->referenceTypeId instead. */
    public function getReferenceTypeId(): NodeId
    {
        return $this->referenceTypeId;
    }

    /** @deprecated Access the public property directly instead. Use ->isForward instead. */
    public function isForward(): bool
    {
        return $this->isForward;
    }

    /** @deprecated Access the public property directly instead. Use ->nodeId instead. */
    public function getNodeId(): NodeId
    {
        return $this->nodeId;
    }

    /** @deprecated Access the public property directly instead. Use ->browseName instead. */
    public function getBrowseName(): QualifiedName
    {
        return $this->browseName;
    }

    /** @deprecated Access the public property directly instead. Use ->displayName instead. */
    public function getDisplayName(): LocalizedText
    {
        return $this->displayName;
    }

    /** @deprecated Access the public property directly instead. Use ->nodeClass instead. */
    public function getNodeClass(): NodeClass
    {
        return $this->nodeClass;
    }

    /** @deprecated Access the public property directly instead. Use ->typeDefinition instead. */
    public function getTypeDefinition(): ?NodeId
    {
        return $this->typeDefinition;
    }
}
