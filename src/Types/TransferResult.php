<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Types;

/**
 * Result of transferring a single subscription to a new session.
 *
 * @see \PhpOpcua\Client\OpcUaClientInterface::transferSubscriptions()
 */
readonly class TransferResult
{
    /**
     * @param int $statusCode
     * @param int[] $availableSequenceNumbers
     */
    public function __construct(
        public int $statusCode,
        public array $availableSequenceNumbers,
    ) {
    }
}
