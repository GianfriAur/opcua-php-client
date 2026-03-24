<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Event;

use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Dispatched after data type discovery completes.
 *
 * @see \Gianfriaur\OpcuaPhpClient\Client\ManagesTypeDiscoveryTrait::discoverDataTypes()
 */
readonly class DataTypesDiscovered
{
    public function __construct(
        public OpcUaClientInterface $client,
        public ?int $namespaceIndex,
        public int $count,
    ) {
    }
}
