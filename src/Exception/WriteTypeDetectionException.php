<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Exception;

/**
 * Thrown when the write type cannot be determined automatically.
 *
 * This occurs when auto-detect is enabled but the node has no readable value (Variant is null),
 * or when auto-detect is disabled and no explicit type was provided.
 */
class WriteTypeDetectionException extends OpcUaException
{
}
