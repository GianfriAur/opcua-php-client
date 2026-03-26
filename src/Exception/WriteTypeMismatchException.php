<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Exception;

use PhpOpcua\Client\Types\BuiltinType;
use PhpOpcua\Client\Types\NodeId;

/**
 * Thrown when the explicit type passed to write() does not match the type detected on the node.
 *
 * Only thrown when auto-detect is enabled and the user provides a type that differs
 * from the node's current Variant type.
 */
class WriteTypeMismatchException extends OpcUaException
{
    /**
     * @param NodeId $nodeId The node being written to.
     * @param BuiltinType $expectedType The type detected on the node.
     * @param BuiltinType $givenType The type provided by the user.
     * @param string $message Human-readable error message.
     */
    public function __construct(
        public readonly NodeId $nodeId,
        public readonly BuiltinType $expectedType,
        public readonly BuiltinType $givenType,
        string $message,
    ) {
        parent::__construct($message);
    }
}
