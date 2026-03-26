<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Exception;

/**
 * Thrown when the TCP connection to the OPC UA server fails or is lost during an operation.
 */
class ConnectionException extends OpcUaException
{
}
