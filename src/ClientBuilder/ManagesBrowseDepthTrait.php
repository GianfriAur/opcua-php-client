<?php

declare(strict_types=1);

namespace PhpOpcua\Client\ClientBuilder;

/**
 * Provides default maximum depth configuration for recursive browse operations.
 */
trait ManagesBrowseDepthTrait
{
    private int $defaultBrowseMaxDepth = 10;

    /**
     * Set the default maximum depth for recursive browse operations.
     *
     * @param int $maxDepth Maximum depth (-1 for unlimited up to internal cap).
     * @return self
     */
    public function setDefaultBrowseMaxDepth(int $maxDepth): self
    {
        $this->defaultBrowseMaxDepth = $maxDepth;

        return $this;
    }

    /**
     * Get the default maximum depth for recursive browse operations.
     *
     * @return int
     */
    public function getDefaultBrowseMaxDepth(): int
    {
        return $this->defaultBrowseMaxDepth;
    }
}
