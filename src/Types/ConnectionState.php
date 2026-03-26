<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Types;

/**
 * Client connection lifecycle states.
 */
enum ConnectionState
{
    case Disconnected;
    case Connected;
    case Broken;
}
