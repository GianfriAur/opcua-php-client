<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

use DateTimeImmutable;

readonly class DataValue
{
    /**
     * @param ?Variant $value
     * @param int $statusCode
     * @param ?DateTimeImmutable $sourceTimestamp
     * @param ?DateTimeImmutable $serverTimestamp
     */
    public function __construct(
        private ?Variant           $value = null,
        public int                $statusCode = 0,
        public ?DateTimeImmutable $sourceTimestamp = null,
        public ?DateTimeImmutable $serverTimestamp = null,
    )
    {
    }

    public function getValue(): mixed
    {
        return $this->value?->getValue();
    }

    /** @deprecated Access the public property directly instead. Use ->value instead. */
    public function getVariant(): ?Variant
    {
        return $this->value;
    }

    /** @deprecated Access the public property directly instead. Use ->statusCode instead. */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /** @deprecated Access the public property directly instead. Use ->sourceTimestamp instead. */
    public function getSourceTimestamp(): ?DateTimeImmutable
    {
        return $this->sourceTimestamp;
    }

    /** @deprecated Access the public property directly instead. Use ->serverTimestamp instead. */
    public function getServerTimestamp(): ?DateTimeImmutable
    {
        return $this->serverTimestamp;
    }

    public function getEncodingMask(): int
    {
        $mask = 0;
        if ($this->value !== null) {
            $mask |= 0x01;
        }
        if ($this->statusCode !== 0) {
            $mask |= 0x02;
        }
        if ($this->sourceTimestamp !== null) {
            $mask |= 0x04;
        }
        if ($this->serverTimestamp !== null) {
            $mask |= 0x08;
        }

        return $mask;
    }
}
