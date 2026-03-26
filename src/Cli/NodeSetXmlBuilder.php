<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Cli;

use DOMDocument;
use DOMElement;
use PhpOpcua\Client\Types\NodeClass;

/**
 * Builds a NodeSet2.xml document from a collection of discovered OPC UA nodes.
 */
class NodeSetXmlBuilder
{
    private const NS_UA = 'http://opcfoundation.org/UA/2011/03/UANodeSet.xsd';

    private const STANDARD_ALIASES = [
        'i=1' => 'Boolean',
        'i=2' => 'SByte',
        'i=3' => 'Byte',
        'i=4' => 'Int16',
        'i=5' => 'UInt16',
        'i=6' => 'Int32',
        'i=7' => 'UInt32',
        'i=8' => 'Int64',
        'i=9' => 'UInt64',
        'i=10' => 'Float',
        'i=11' => 'Double',
        'i=12' => 'String',
        'i=13' => 'DateTime',
        'i=14' => 'Guid',
        'i=15' => 'ByteString',
        'i=17' => 'NodeId',
        'i=20' => 'QualifiedName',
        'i=21' => 'LocalizedText',
        'i=22' => 'Structure',
        'i=24' => 'BaseDataType',
        'i=25' => 'DiagnosticInfo',
        'i=29' => 'Enumeration',
        'i=35' => 'Organizes',
        'i=38' => 'HasEncoding',
        'i=40' => 'HasTypeDefinition',
        'i=44' => 'HasSubtype',
        'i=45' => 'HasSubtype',
        'i=46' => 'HasProperty',
        'i=47' => 'HasComponent',
    ];

    private const NODE_CLASS_TO_TAG = [
        NodeClass::Object->value => 'UAObject',
        NodeClass::Variable->value => 'UAVariable',
        NodeClass::Method->value => 'UAMethod',
        NodeClass::ObjectType->value => 'UAObjectType',
        NodeClass::VariableType->value => 'UAVariableType',
        NodeClass::ReferenceType->value => 'UAReferenceType',
        NodeClass::DataType->value => 'UADataType',
    ];

    /**
     * Build a NodeSet2.xml string from discovered nodes.
     *
     * @param array<array{nodeId: string, nodeClass: int, browseName: string, displayName: string, references: array, attributes: array}> $nodes
     * @param string[] $namespaceUris
     * @return string
     */
    public function build(array $nodes, array $namespaceUris): string
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;

        $root = $dom->createElementNS(self::NS_UA, 'UANodeSet');
        $dom->appendChild($root);

        $this->writeNamespaceUris($dom, $root, $namespaceUris);
        $this->writeAliases($dom, $root);

        foreach ($nodes as $node) {
            $this->writeNode($dom, $root, $node);
        }

        return $dom->saveXML() ?: '';
    }

    /**
     * @param DOMDocument $dom
     * @param DOMElement $root
     * @param string[] $uris
     * @return void
     */
    private function writeNamespaceUris(DOMDocument $dom, DOMElement $root, array $uris): void
    {
        if (empty($uris)) {
            return;
        }

        $nsUris = $dom->createElement('NamespaceUris');
        foreach ($uris as $uri) {
            $el = $dom->createElement('Uri', $uri);
            $nsUris->appendChild($el);
        }
        $root->appendChild($nsUris);
    }

    /**
     * @param DOMDocument $dom
     * @param DOMElement $root
     * @return void
     */
    private function writeAliases(DOMDocument $dom, DOMElement $root): void
    {
        $aliases = $dom->createElement('Aliases');
        foreach (self::STANDARD_ALIASES as $nodeId => $name) {
            $alias = $dom->createElement('Alias', $nodeId);
            $alias->setAttribute('Alias', $name);
            $aliases->appendChild($alias);
        }
        $root->appendChild($aliases);
    }

    /**
     * @param DOMDocument $dom
     * @param DOMElement $root
     * @param array $node
     * @return void
     */
    private function writeNode(DOMDocument $dom, DOMElement $root, array $node): void
    {
        $tag = self::NODE_CLASS_TO_TAG[$node['nodeClass']] ?? null;
        if ($tag === null) {
            return;
        }

        $el = $dom->createElement($tag);
        $el->setAttribute('NodeId', $node['nodeId']);
        $el->setAttribute('BrowseName', $node['browseName']);

        $attrs = $node['attributes'] ?? [];

        if ($node['nodeClass'] === NodeClass::Variable->value || $node['nodeClass'] === NodeClass::VariableType->value) {
            if (isset($attrs['DataType'])) {
                $el->setAttribute('DataType', $attrs['DataType']);
            }
            if (isset($attrs['ValueRank']) && $attrs['ValueRank'] !== -1) {
                $el->setAttribute('ValueRank', (string) $attrs['ValueRank']);
            }
        }

        if (isset($attrs['IsAbstract']) && $attrs['IsAbstract']) {
            $el->setAttribute('IsAbstract', 'true');
        }

        if ($node['nodeClass'] === NodeClass::ReferenceType->value && isset($attrs['Symmetric']) && $attrs['Symmetric']) {
            $el->setAttribute('Symmetric', 'true');
        }

        $displayName = $dom->createElement('DisplayName', htmlspecialchars($node['displayName'], ENT_XML1));
        $el->appendChild($displayName);

        $this->writeReferences($dom, $el, $node['references'] ?? []);

        if ($node['nodeClass'] === NodeClass::DataType->value && isset($attrs['definition'])) {
            $this->writeDefinition($dom, $el, $node['browseName'], $attrs['definition']);
        }

        $root->appendChild($el);
    }

    /**
     * @param DOMDocument $dom
     * @param DOMElement $parent
     * @param array $references
     * @return void
     */
    private function writeReferences(DOMDocument $dom, DOMElement $parent, array $references): void
    {
        if (empty($references)) {
            return;
        }

        $refsEl = $dom->createElement('References');
        foreach ($references as $ref) {
            $refEl = $dom->createElement('Reference', $ref['targetId']);
            $refEl->setAttribute('ReferenceType', $ref['referenceType']);
            if (isset($ref['isForward']) && ! $ref['isForward']) {
                $refEl->setAttribute('IsForward', 'false');
            }
            $refsEl->appendChild($refEl);
        }
        $parent->appendChild($refsEl);
    }

    /**
     * @param DOMDocument $dom
     * @param DOMElement $parent
     * @param string $name
     * @param array $definition
     * @return void
     */
    private function writeDefinition(DOMDocument $dom, DOMElement $parent, string $name, array $definition): void
    {
        $cleanName = $name;
        if (str_contains($cleanName, ':')) {
            $cleanName = substr($cleanName, strpos($cleanName, ':') + 1);
        }

        $defEl = $dom->createElement('Definition');
        $defEl->setAttribute('Name', $cleanName);

        foreach ($definition['fields'] ?? [] as $field) {
            $fieldEl = $dom->createElement('Field');
            $fieldEl->setAttribute('Name', $field['name']);

            if (isset($field['dataType'])) {
                $fieldEl->setAttribute('DataType', $field['dataType']);
            }

            if (isset($field['value'])) {
                $fieldEl->setAttribute('Value', (string) $field['value']);
            }

            if (isset($field['valueRank']) && $field['valueRank'] !== -1) {
                $fieldEl->setAttribute('ValueRank', (string) $field['valueRank']);
            }

            if (isset($field['isOptional']) && $field['isOptional']) {
                $fieldEl->setAttribute('IsOptional', 'true');
            }

            $defEl->appendChild($fieldEl);
        }

        $parent->appendChild($defEl);
    }
}
