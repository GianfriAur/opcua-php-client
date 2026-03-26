<?php

declare(strict_types=1);

use PhpOpcua\Client\Cli\NodeSetXmlBuilder;
use PhpOpcua\Client\Types\NodeClass;

describe('NodeSetXmlBuilder', function () {

    it('builds valid XML with namespace URIs', function () {
        $builder = new NodeSetXmlBuilder();
        $xml = $builder->build([], ['http://example.com/test/']);

        expect($xml)->toContain('<?xml');
        expect($xml)->toContain('UANodeSet');
        expect($xml)->toContain('http://example.com/test/');
        expect($xml)->toContain('<Aliases>');
    });

    it('builds UAObject node', function () {
        $builder = new NodeSetXmlBuilder();
        $nodes = [
            [
                'nodeId' => 'ns=1;i=1000',
                'nodeClass' => NodeClass::Object->value,
                'browseName' => '1:TestFolder',
                'displayName' => 'TestFolder',
                'references' => [],
                'attributes' => [],
            ],
        ];

        $xml = $builder->build($nodes, ['http://example.com/']);

        expect($xml)->toContain('<UAObject');
        expect($xml)->toContain('NodeId="ns=1;i=1000"');
        expect($xml)->toContain('BrowseName="1:TestFolder"');
        expect($xml)->toContain('<DisplayName>TestFolder</DisplayName>');
    });

    it('builds UAVariable with DataType and ValueRank', function () {
        $builder = new NodeSetXmlBuilder();
        $nodes = [
            [
                'nodeId' => 'ns=1;i=2001',
                'nodeClass' => NodeClass::Variable->value,
                'browseName' => '1:Temperature',
                'displayName' => 'Temperature',
                'references' => [],
                'attributes' => ['DataType' => 'i=11', 'ValueRank' => 1],
            ],
        ];

        $xml = $builder->build($nodes, []);

        expect($xml)->toContain('<UAVariable');
        expect($xml)->toContain('DataType="i=11"');
        expect($xml)->toContain('ValueRank="1"');
    });

    it('builds UADataType with Definition', function () {
        $builder = new NodeSetXmlBuilder();
        $nodes = [
            [
                'nodeId' => 'ns=1;i=3001',
                'nodeClass' => NodeClass::DataType->value,
                'browseName' => '1:TestPoint',
                'displayName' => 'TestPoint',
                'references' => [],
                'attributes' => [
                    'definition' => [
                        'fields' => [
                            ['name' => 'X', 'dataType' => 'i=11', 'valueRank' => -1, 'isOptional' => false],
                            ['name' => 'Y', 'dataType' => 'i=11', 'valueRank' => -1, 'isOptional' => false],
                        ],
                    ],
                ],
            ],
        ];

        $xml = $builder->build($nodes, []);

        expect($xml)->toContain('<UADataType');
        expect($xml)->toContain('<Definition Name="TestPoint"');
        expect($xml)->toContain('Name="X"');
        expect($xml)->toContain('DataType="i=11"');
    });

    it('builds references for nodes', function () {
        $builder = new NodeSetXmlBuilder();
        $nodes = [
            [
                'nodeId' => 'ns=1;i=1000',
                'nodeClass' => NodeClass::Object->value,
                'browseName' => '1:Folder',
                'displayName' => 'Folder',
                'references' => [
                    ['referenceType' => 'i=47', 'isForward' => true, 'targetId' => 'ns=1;i=2001'],
                    ['referenceType' => 'i=35', 'isForward' => false, 'targetId' => 'i=85'],
                ],
                'attributes' => [],
            ],
        ];

        $xml = $builder->build($nodes, []);

        expect($xml)->toContain('<References>');
        expect($xml)->toContain('ReferenceType="i=47"');
        expect($xml)->toContain('ns=1;i=2001');
        expect($xml)->toContain('IsForward="false"');
    });

    it('builds UAObjectType with IsAbstract', function () {
        $builder = new NodeSetXmlBuilder();
        $nodes = [
            [
                'nodeId' => 'ns=1;i=4001',
                'nodeClass' => NodeClass::ObjectType->value,
                'browseName' => '1:DeviceType',
                'displayName' => 'DeviceType',
                'references' => [],
                'attributes' => ['IsAbstract' => true],
            ],
        ];

        $xml = $builder->build($nodes, []);

        expect($xml)->toContain('<UAObjectType');
        expect($xml)->toContain('IsAbstract="true"');
    });

    it('builds enum Definition with Value attributes', function () {
        $builder = new NodeSetXmlBuilder();
        $nodes = [
            [
                'nodeId' => 'ns=1;i=3010',
                'nodeClass' => NodeClass::DataType->value,
                'browseName' => '1:StatusEnum',
                'displayName' => 'StatusEnum',
                'references' => [],
                'attributes' => [
                    'definition' => [
                        'fields' => [
                            ['name' => 'Off', 'value' => 0],
                            ['name' => 'Running', 'value' => 1],
                        ],
                    ],
                ],
            ],
        ];

        $xml = $builder->build($nodes, []);

        expect($xml)->toContain('Name="Off"');
        expect($xml)->toContain('Value="0"');
        expect($xml)->toContain('Name="Running"');
        expect($xml)->toContain('Value="1"');
    });

    it('output is valid XML', function () {
        $builder = new NodeSetXmlBuilder();
        $nodes = [
            [
                'nodeId' => 'ns=1;i=1',
                'nodeClass' => NodeClass::Object->value,
                'browseName' => '1:Test',
                'displayName' => 'Test',
                'references' => [],
                'attributes' => [],
            ],
        ];

        $xml = $builder->build($nodes, ['http://test/']);
        $parsed = simplexml_load_string($xml);

        expect($parsed)->not->toBeFalse();
    });
});
