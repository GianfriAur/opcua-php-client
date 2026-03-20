<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

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
