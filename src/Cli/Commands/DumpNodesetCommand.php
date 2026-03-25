<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Cli\Commands;

use Gianfriaur\OpcuaPhpClient\Cli\NodeSetXmlBuilder;
use Gianfriaur\OpcuaPhpClient\Cli\Output\OutputInterface;
use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;
use Gianfriaur\OpcuaPhpClient\Types\AttributeId;
use Gianfriaur\OpcuaPhpClient\Types\NodeClass;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;
use Gianfriaur\OpcuaPhpClient\Types\StatusCode;

/**
 * Dumps the server address space to a NodeSet2.xml file.
 */
class DumpNodesetCommand implements CommandInterface
{
    private const NAMESPACE_ARRAY_NODE = 'i=2255';

    /**
     * {@inheritDoc}
     */
    public function getName(): string
    {
        return 'dump:nodeset';
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return 'Dump the server address space to a NodeSet2.xml file';
    }

    /**
     * {@inheritDoc}
     */
    public function getUsage(): string
    {
        return 'dump:nodeset <endpoint> --output=<file.xml> [--namespace=<index>]';
    }

    /**
     * {@inheritDoc}
     */
    public function requiresConnection(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function execute(OpcUaClientInterface $client, array $arguments, array $options, OutputInterface $output): int
    {
        $outputFile = (string) ($options['output'] ?? '');
        if ($outputFile === '') {
            $output->error('Usage: opcua-cli dump:nodeset <endpoint> --output=<file.xml> [--namespace=<index>]');

            return 1;
        }

        $namespaceFilter = isset($options['namespace']) ? (int) $options['namespace'] : null;

        $namespaceUris = $this->readNamespaceArray($client);
        $output->writeln('Namespace URIs:');
        foreach ($namespaceUris as $i => $uri) {
            $output->writeln("  [{$i}] {$uri}");
        }
        $output->writeln('');

        $output->writeln('Browsing address space...');
        $tree = $client->browseRecursive(NodeId::numeric(0, 84), maxDepth: 50);
        $output->writeln('Found ' . count($tree) . ' top-level nodes');
        $output->writeln('');

        $output->writeln('Discovering data types...');
        $discovered = $client->discoverDataTypes($namespaceFilter, useCache: false);
        $output->writeln("Discovered {$discovered} structured type(s) via discoverDataTypes()");
        $output->writeln('');

        $output->writeln('Collecting nodes and reading attributes...');
        $collectedNodes = [];
        $this->collectNodes($client, $tree, $collectedNodes, $namespaceFilter, $namespaceUris);
        $output->writeln('Collected ' . count($collectedNodes) . ' nodes');
        $output->writeln('');

        $output->writeln('Resolving enum values...');
        $this->resolveEnumDefinitions($client, $collectedNodes, $output);
        $output->writeln('');

        $filteredUris = [];
        if ($namespaceFilter !== null) {
            if (isset($namespaceUris[$namespaceFilter])) {
                $filteredUris[] = $namespaceUris[$namespaceFilter];
            }
        } else {
            for ($i = 1; $i < count($namespaceUris); $i++) {
                $filteredUris[] = $namespaceUris[$i];
            }
        }

        $output->writeln('Building XML...');
        $builder = new NodeSetXmlBuilder();
        $xml = $builder->build($collectedNodes, $filteredUris);

        file_put_contents($outputFile, $xml);
        $output->writeln("Written: {$outputFile}");
        $output->writeln('');
        $output->writeln('Done. ' . count($collectedNodes) . ' nodes exported.');

        return 0;
    }

    /**
     * @param OpcUaClientInterface $client
     * @return string[]
     */
    private function readNamespaceArray(OpcUaClientInterface $client): array
    {
        $dv = $client->read(self::NAMESPACE_ARRAY_NODE);
        $value = $dv->getValue();

        if (is_array($value)) {
            return $value;
        }

        return ['http://opcfoundation.org/UA/'];
    }

    /**
     * @param OpcUaClientInterface $client
     * @param array $tree
     * @param array &$collected
     * @param ?int $namespaceFilter
     * @param string[] $namespaceUris
     * @return void
     */
    private function collectNodes(
        OpcUaClientInterface $client,
        array $tree,
        array &$collected,
        ?int $namespaceFilter,
        array $namespaceUris,
    ): void {
        foreach ($tree as $browseNode) {
            $ref = $browseNode->reference;
            $nodeId = $ref->nodeId;

            if ($namespaceFilter !== null && $nodeId->namespaceIndex !== $namespaceFilter) {
                if ($browseNode->hasChildren()) {
                    $this->collectNodes($client, $browseNode->getChildren(), $collected, $namespaceFilter, $namespaceUris);
                }

                continue;
            }

            if ($namespaceFilter === null && $nodeId->namespaceIndex === 0) {
                if ($browseNode->hasChildren()) {
                    $this->collectNodes($client, $browseNode->getChildren(), $collected, $namespaceFilter, $namespaceUris);
                }

                continue;
            }

            $nodeIdStr = (string) $nodeId;
            if (isset($collected[$nodeIdStr])) {
                continue;
            }

            $references = $this->readReferences($client, $nodeId);
            $attributes = $this->readNodeAttributes($client, $nodeId, $ref->nodeClass);

            $nsPrefix = $nodeId->namespaceIndex > 0 ? "{$nodeId->namespaceIndex}:" : '';

            $collected[$nodeIdStr] = [
                'nodeId' => $nodeIdStr,
                'nodeClass' => $ref->nodeClass->value,
                'browseName' => $nsPrefix . ($ref->browseName->name ?? ''),
                'displayName' => $ref->displayName->text ?? $ref->browseName->name ?? '',
                'references' => $references,
                'attributes' => $attributes,
            ];

            if ($browseNode->hasChildren()) {
                $this->collectNodes($client, $browseNode->getChildren(), $collected, $namespaceFilter, $namespaceUris);
            }
        }
    }

    /**
     * @param OpcUaClientInterface $client
     * @param NodeId $nodeId
     * @return array
     */
    private function readReferences(OpcUaClientInterface $client, NodeId $nodeId): array
    {
        $refs = [];

        try {
            $allRefs = $client->browseAll($nodeId);
            foreach ($allRefs as $ref) {
                $refs[] = [
                    'referenceType' => (string) $ref->referenceTypeId,
                    'isForward' => $ref->isForward,
                    'targetId' => (string) $ref->nodeId,
                ];
            }
        } catch (\Throwable) {
        }

        return $refs;
    }

    /**
     * @param OpcUaClientInterface $client
     * @param NodeId $nodeId
     * @param NodeClass $nodeClass
     * @return array
     */
    private function readNodeAttributes(OpcUaClientInterface $client, NodeId $nodeId, NodeClass $nodeClass): array
    {
        $attrs = [];

        try {
            if ($nodeClass === NodeClass::Variable || $nodeClass === NodeClass::VariableType) {
                $dtDv = $client->read($nodeId, AttributeId::DataType);
                $dtVal = $dtDv->getValue();
                if ($dtVal instanceof NodeId) {
                    $attrs['DataType'] = (string) $dtVal;
                }

                $vrDv = $client->read($nodeId, AttributeId::ValueRank);
                $vrVal = $vrDv->getValue();
                if (is_int($vrVal)) {
                    $attrs['ValueRank'] = $vrVal;
                }
            }

            if (in_array($nodeClass, [NodeClass::ObjectType, NodeClass::VariableType, NodeClass::DataType, NodeClass::ReferenceType], true)) {
                $absDv = $client->read($nodeId, AttributeId::IsAbstract);
                $absVal = $absDv->getValue();
                if ($absVal === true) {
                    $attrs['IsAbstract'] = true;
                }
            }

            if ($nodeClass === NodeClass::ReferenceType) {
                $symDv = $client->read($nodeId, AttributeId::Symmetric);
                $symVal = $symDv->getValue();
                if ($symVal === true) {
                    $attrs['Symmetric'] = true;
                }
            }

            if ($nodeClass === NodeClass::DataType) {
                $attrs['definition'] = $this->readDataTypeDefinition($client, $nodeId);
            }
        } catch (\Throwable) {
        }

        return $attrs;
    }

    /**
     * @param OpcUaClientInterface $client
     * @param NodeId $nodeId
     * @return ?array
     */
    private function readDataTypeDefinition(OpcUaClientInterface $client, NodeId $nodeId): ?array
    {
        try {
            $dv = $client->read($nodeId, AttributeId::DataTypeDefinition);
            if (! StatusCode::isBad($dv->statusCode)) {
                $value = $dv->getVariant()?->value;

                if ($value instanceof \Gianfriaur\OpcuaPhpClient\Types\StructureDefinition) {
                    return $this->structureDefinitionToArray($value);
                }

                if (is_object($value) && isset($value->body)) {
                    $decoder = new \Gianfriaur\OpcuaPhpClient\Encoding\BinaryDecoder($value->body);
                    $parsed = $this->parseRawDefinition($decoder);
                    if ($parsed !== null) {
                        return $parsed;
                    }
                }
            }
        } catch (\Throwable) {
        }

        return $this->findDefinitionFromDiscovery($client, $nodeId);
    }

    /**
     * @param OpcUaClientInterface $client
     * @param NodeId $nodeId
     * @return ?array
     */
    private function findDefinitionFromDiscovery(OpcUaClientInterface $client, NodeId $nodeId): ?array
    {
        try {
            $refs = $client->browseAll($nodeId);
            foreach ($refs as $ref) {
                if ($ref->displayName->text === 'Default Binary' || $ref->browseName->name === 'Default Binary') {
                    $codec = $client->getExtensionObjectRepository()->get($ref->nodeId);
                    if ($codec instanceof \Gianfriaur\OpcuaPhpClient\Encoding\DynamicCodec) {
                        $defRef = new \ReflectionProperty($codec, 'definition');
                        $def = $defRef->getValue($codec);
                        if ($def instanceof \Gianfriaur\OpcuaPhpClient\Types\StructureDefinition) {
                            return $this->structureDefinitionToArray($def);
                        }
                    }
                }
            }
        } catch (\Throwable) {
        }

        return null;
    }

    /**
     * @param OpcUaClientInterface $client
     * @param array &$collectedNodes
     * @return void
     */
    /**
     * @param OpcUaClientInterface $client
     * @param array &$collectedNodes
     * @param OutputInterface $output
     * @return void
     */
    private function resolveEnumDefinitions(OpcUaClientInterface $client, array &$collectedNodes, OutputInterface $output): void
    {
        $resolved = 0;
        $dataTypeCount = 0;
        foreach ($collectedNodes as $nodeIdStr => &$node) {
            if ($node['nodeClass'] !== NodeClass::DataType->value) {
                continue;
            }
            $dataTypeCount++;

            if (isset($node['attributes']['definition']) && $node['attributes']['definition'] !== null) {
                continue;
            }

            $enumDef = $this->readEnumValues($client, NodeId::parse($nodeIdStr));
            if ($enumDef !== null) {
                $node['attributes']['definition'] = $enumDef;
                $resolved++;
            }
        }
        unset($node);

        $output->writeln("Found {$dataTypeCount} DataType node(s), resolved {$resolved} enum definition(s)");
    }

    /**
     * @param OpcUaClientInterface $client
     * @param NodeId $dataTypeNodeId
     * @return ?array
     */
    private function readEnumValues(OpcUaClientInterface $client, NodeId $dataTypeNodeId): ?array
    {
        try {
            $refs = $client->browseAll($dataTypeNodeId);
            foreach ($refs as $ref) {
                $name = $ref->browseName->name ?? '';
                if ($name !== 'EnumStrings' && $name !== 'EnumValues') {
                    continue;
                }

                $dv = $client->read($ref->nodeId);
                $value = $dv->getValue();

                if ($name === 'EnumStrings' && is_array($value)) {
                    return $this->enumStringsToDefinition($value);
                }

                if ($name === 'EnumValues' && is_array($value)) {
                    return $this->enumValuesToDefinition($value);
                }
            }
        } catch (\Throwable) {
        }

        return null;
    }

    /**
     * @param array $strings
     * @return array
     */
    private function enumStringsToDefinition(array $strings): array
    {
        $fields = [];
        foreach ($strings as $i => $str) {
            $text = is_object($str) && isset($str->text) ? $str->text : (string) $str;
            $fields[] = ['name' => $text, 'value' => $i];
        }

        return ['fields' => $fields];
    }

    /**
     * @param array $values
     * @return array
     */
    private function enumValuesToDefinition(array $values): array
    {
        $fields = [];
        foreach ($values as $ev) {
            if (is_array($ev)) {
                $name = $ev['displayName'] ?? $ev['DisplayName'] ?? 'Value_' . ($ev['value'] ?? $ev['Value'] ?? 0);
                $val = $ev['value'] ?? $ev['Value'] ?? 0;
                if (is_object($name) && isset($name->text)) {
                    $name = $name->text;
                }
                $fields[] = ['name' => (string) $name, 'value' => (int) $val];
            }
        }

        return ['fields' => $fields];
    }

    /**
     * @param \Gianfriaur\OpcuaPhpClient\Types\StructureDefinition $def
     * @return array
     */
    private function structureDefinitionToArray(\Gianfriaur\OpcuaPhpClient\Types\StructureDefinition $def): array
    {
        $fields = [];
        foreach ($def->fields as $field) {
            $fields[] = [
                'name' => $field->name,
                'dataType' => (string) $field->dataType,
                'valueRank' => $field->valueRank,
                'isOptional' => $field->isOptional,
            ];
        }

        return ['fields' => $fields];
    }

    /**
     * @param \Gianfriaur\OpcuaPhpClient\Encoding\BinaryDecoder $decoder
     * @return ?array
     */
    private function parseRawDefinition(\Gianfriaur\OpcuaPhpClient\Encoding\BinaryDecoder $decoder): ?array
    {
        try {
            $parsed = \Gianfriaur\OpcuaPhpClient\Encoding\StructureDefinitionParser::parse($decoder);

            return $this->structureDefinitionToArray($parsed);
        } catch (\Throwable) {
            return null;
        }
    }
}
