<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use PhpOpcua\Client\OpcUaClientInterface;

/**
 * Dispatched after data type discovery completes.
 *
 * @see \PhpOpcua\Client\Client\ManagesTypeDiscoveryTrait::discoverDataTypes()
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
