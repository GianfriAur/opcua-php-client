<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

/**
 * Holds the result of an OPC UA method Call operation.
 *
 * @see \Gianfriaur\OpcuaPhpClient\OpcuaClient::call()
 */
readonly class CallResult
{
    /**
     * @param int $statusCode
     * @param int[] $inputArgumentResults
     * @param Variant[] $outputArguments
     */
    public function __construct(
        public int   $statusCode,
        public array $inputArgumentResults,
        public array $outputArguments,
    )
    {
    }
}
