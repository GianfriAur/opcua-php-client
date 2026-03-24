<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

/**
 * Represents an OPC UA Variant, a union type that can hold any built-in data type value.
 */
readonly class Variant
{
    /**
     * @param BuiltinType $type
     * @param mixed $value
     * @param null|int[] $dimensions
     */
    public function __construct(
        public BuiltinType $type,
        public mixed $value,
        public ?array $dimensions = null,
    ) {
    }

    /**
     * @deprecated Access the public property directly instead. Use ->type instead.
     * @return BuiltinType
     * @see Variant::$type
     */
    public function getType(): BuiltinType
    {
        return $this->type;
    }

    /**
     * @deprecated Access the public property directly instead. Use ->value instead.
     * @return mixed
     * @see Variant::$value
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * @deprecated Access the public property directly instead. Use ->dimensions instead.
     * @return int[]|null
     * @see Variant::$dimensions
     */
    public function getDimensions(): ?array
    {
        return $this->dimensions;
    }

    /**
     * Checks whether this Variant holds a multi-dimensional array value.
     *
     * @return bool
     */
    public function isMultiDimensional(): bool
    {
        return $this->dimensions !== null && count($this->dimensions) > 1;
    }
}
