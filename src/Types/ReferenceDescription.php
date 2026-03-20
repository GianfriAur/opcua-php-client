<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

/**
 * Represents an OPC UA ReferenceDescription returned from a Browse operation.
 */
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

    /**
     * @deprecated Access the public property directly instead. Use ->referenceTypeId instead.
     * @return NodeId
     * @see ReferenceDescription::$referenceTypeId
     */
    public function getReferenceTypeId(): NodeId
    {
        return $this->referenceTypeId;
    }

    /**
     * @deprecated Access the public property directly instead. Use ->isForward instead.
     * @return bool
     * @see ReferenceDescription::$isForward
     */
    public function isForward(): bool
    {
        return $this->isForward;
    }

    /**
     * @deprecated Access the public property directly instead. Use ->nodeId instead.
     * @return NodeId
     * @see ReferenceDescription::$nodeId
     */
    public function getNodeId(): NodeId
    {
        return $this->nodeId;
    }

    /**
     * @deprecated Access the public property directly instead. Use ->browseName instead.
     * @return QualifiedName
     * @see ReferenceDescription::$browseName
     */
    public function getBrowseName(): QualifiedName
    {
        return $this->browseName;
    }

    /**
     * @deprecated Access the public property directly instead. Use ->displayName instead.
     * @return LocalizedText
     * @see ReferenceDescription::$displayName
     */
    public function getDisplayName(): LocalizedText
    {
        return $this->displayName;
    }

    /**
     * @deprecated Access the public property directly instead. Use ->nodeClass instead.
     * @return NodeClass
     * @see ReferenceDescription::$nodeClass
     */
    public function getNodeClass(): NodeClass
    {
        return $this->nodeClass;
    }

    /**
     * @deprecated Access the public property directly instead. Use ->typeDefinition instead.
     * @return ?NodeId
     * @see ReferenceDescription::$typeDefinition
     */
    public function getTypeDefinition(): ?NodeId
    {
        return $this->typeDefinition;
    }
}
