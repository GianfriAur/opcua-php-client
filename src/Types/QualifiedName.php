<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

readonly class QualifiedName
{
    /**
     * @param int $namespaceIndex
     * @param string $name
     */
    public function __construct(
        public int    $namespaceIndex,
        public string $name,
    )
    {
    }

    /** @deprecated Access the public property directly instead. Use ->namespaceIndex instead. */
    public function getNamespaceIndex(): int
    {
        return $this->namespaceIndex;
    }

    /** @deprecated Access the public property directly instead. Use ->name instead. */
    public function getName(): string
    {
        return $this->name;
    }

    public function __toString(): string
    {
        if ($this->namespaceIndex === 0) {
            return $this->name;
        }

        return $this->namespaceIndex . ':' . $this->name;
    }
}
