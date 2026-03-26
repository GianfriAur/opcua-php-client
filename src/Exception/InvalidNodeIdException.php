<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Exception;

/**
 * Thrown when a NodeId string cannot be parsed into a valid node identifier.
 */
class InvalidNodeIdException extends OpcUaException
{
}
