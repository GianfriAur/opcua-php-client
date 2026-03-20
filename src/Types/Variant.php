<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

readonly class Variant
{
    /**
     * @param BuiltinType $type
     * @param mixed $value
     * @param null|int[] $dimensions
     */
    public function __construct(
        public BuiltinType $type,
        public mixed       $value,
        public ?array      $dimensions = null,
    )
    {
    }

    /** @deprecated Access the public property directly instead. Use ->type instead. */
    public function getType(): BuiltinType
    {
        return $this->type;
    }

    /** @deprecated Access the public property directly instead. Use ->value instead. */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @deprecated Access the public property directly instead. Use ->dimensions instead.
     * @return int[]|null
     */
    public function getDimensions(): ?array
    {
        return $this->dimensions;
    }

    public function isMultiDimensional(): bool
    {
        return $this->dimensions !== null && count($this->dimensions) > 1;
    }
}
