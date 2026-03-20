<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

class CallResult
{
    /**
     * @param int $statusCode
     * @param int[] $inputArgumentResults
     * @param Variant[] $outputArguments
     */
    public function __construct(
        public readonly int   $statusCode,
        public readonly array $inputArgumentResults,
        public readonly array $outputArguments,
    )
    {
    }
}
