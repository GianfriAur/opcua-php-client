<?php

declare(strict_types=1);

namespace PhpOpcua\Client\ClientBuilder;

use PhpOpcua\Client\Repository\GeneratedTypeRegistrar;

/**
 * Provides read/write configuration: auto-detect write type, metadata caching, and generated type loading.
 */
trait ManagesReadWriteConfigTrait
{
    private bool $autoDetectWriteType = true;

    private bool $readMetadataCache = false;

    /** @var array<string, class-string<\BackedEnum>> */
    private array $enumMappings = [];

    /**
     * Enable or disable automatic write type detection.
     *
     * When enabled (default), write operations without an explicit type will read the node
     * first to determine the correct BuiltinType. When a type is provided explicitly,
     * it is validated against the detected type. Detected types are cached via PSR-16.
     *
     * When disabled, an explicit BuiltinType must be passed to every write call.
     *
     * @param bool $enabled Whether to enable auto-detection.
     * @return self
     */
    public function setAutoDetectWriteType(bool $enabled): self
    {
        $this->autoDetectWriteType = $enabled;

        return $this;
    }

    /**
     * Enable or disable caching for metadata read operations.
     *
     * When enabled, read operations for non-Value attributes (DisplayName, BrowseName,
     * DataType, NodeClass, Description, etc.) are cached via PSR-16. The Value attribute
     * (id 13) is never cached regardless of this setting.
     *
     * Disabled by default.
     *
     * @param bool $enabled Whether to enable metadata caching.
     * @return self
     */
    public function setReadMetadataCache(bool $enabled): self
    {
        $this->readMetadataCache = $enabled;

        return $this;
    }

    /**
     * Load generated types from a NodeSet2.xml code generator registrar.
     *
     * Registers ExtensionObject codecs and enum mappings for automatic value casting.
     * Can be called multiple times to load types from different NodeSet files.
     *
     * @param GeneratedTypeRegistrar $registrar The generated registrar.
     * @return self
     */
    public function loadGeneratedTypes(GeneratedTypeRegistrar $registrar): self
    {
        if (! property_exists($registrar, 'only') || ! $registrar->only) {
            foreach ($registrar->dependencyRegistrars() as $dependency) {
                $this->loadGeneratedTypes($dependency);
            }
        }

        $registrar->registerCodecs($this->extensionObjectRepository);
        $this->enumMappings = array_merge($this->enumMappings, $registrar->getEnumMappings());

        return $this;
    }
}
