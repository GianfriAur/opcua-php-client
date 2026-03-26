<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Types;

/**
 * Represents an OPC UA QualifiedName, consisting of a namespace index and a name string.
 */
readonly class QualifiedName
{
    /**
     * @param int $namespaceIndex
     * @param string $name
     */
    public function __construct(
        public int $namespaceIndex,
        public string $name,
    ) {
    }

    /**
     * @deprecated Access the public property directly instead. Use ->namespaceIndex instead.
     * @return int
     * @see QualifiedName::$namespaceIndex
     */
    public function getNamespaceIndex(): int
    {
        return $this->namespaceIndex;
    }

    /**
     * @deprecated Access the public property directly instead. Use ->name instead.
     * @return string
     * @see QualifiedName::$name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Returns the string representation in the format "namespaceIndex:name" (or just "name" for namespace 0).
     *
     * @return string
     */
    public function __toString(): string
    {
        if ($this->namespaceIndex === 0) {
            return $this->name;
        }

        return $this->namespaceIndex . ':' . $this->name;
    }
}
