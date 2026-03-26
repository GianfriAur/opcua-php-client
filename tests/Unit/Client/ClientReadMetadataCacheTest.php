<?php

declare(strict_types=1);

require_once __DIR__ . '/ClientTraitsCoverageTest.php';

use PhpOpcua\Client\Encoding\BinaryEncoder;
use PhpOpcua\Client\Types\AttributeId;
use PhpOpcua\Client\Types\BuiltinType;

function readResponseMsgString(string $value): string
{
    return buildMsgResponse(634, function (BinaryEncoder $e) use ($value) {
        $e->writeInt32(1);
        $e->writeByte(0x01);
        $e->writeByte(BuiltinType::String->value);
        $e->writeString($value);
        $e->writeInt32(0);
    });
}

describe('Read metadata cache', function () {

    it('does not cache when readMetadataCache is off (default)', function () {
        $mock = new MockTransport();
        $mock->addResponse(readResponseMsgString('Temperature'));
        $mock->addResponse(readResponseMsgString('Temperature'));

        $client = setupConnectedClient($mock);
        $client->read('i=1001', AttributeId::DisplayName);
        $client->read('i=1001', AttributeId::DisplayName);

        expect(count($mock->sent))->toBe(2);
    });

    it('caches metadata attribute when enabled', function () {
        $mock = new MockTransport();
        $mock->addResponse(readResponseMsgString('Temperature'));

        $client = setupConnectedClient($mock);
        setClientProperty($client, 'readMetadataCache', true);

        $dv1 = $client->read('i=1001', AttributeId::DisplayName);
        $dv2 = $client->read('i=1001', AttributeId::DisplayName);

        expect(count($mock->sent))->toBe(1);
        expect($dv1->getValue())->toBe('Temperature');
        expect($dv2->getValue())->toBe('Temperature');
    });

    it('never caches Value attribute even when enabled', function () {
        $mock = new MockTransport();
        $mock->addResponse(readResponseMsg(42));
        $mock->addResponse(readResponseMsg(43));

        $client = setupConnectedClient($mock);
        setClientProperty($client, 'readMetadataCache', true);

        $dv1 = $client->read('i=1001', AttributeId::Value);
        $dv2 = $client->read('i=1001', AttributeId::Value);

        expect(count($mock->sent))->toBe(2);
        expect($dv1->getValue())->toBe(42);
        expect($dv2->getValue())->toBe(43);
    });

    it('refresh bypasses cache and updates it', function () {
        $mock = new MockTransport();
        $mock->addResponse(readResponseMsgString('OldName'));
        $mock->addResponse(readResponseMsgString('NewName'));

        $client = setupConnectedClient($mock);
        setClientProperty($client, 'readMetadataCache', true);

        $dv1 = $client->read('i=1001', AttributeId::DisplayName);
        expect($dv1->getValue())->toBe('OldName');

        $dv2 = $client->read('i=1001', AttributeId::DisplayName, refresh: true);
        expect($dv2->getValue())->toBe('NewName');

        expect(count($mock->sent))->toBe(2);

        $dv3 = $client->read('i=1001', AttributeId::DisplayName);
        expect($dv3->getValue())->toBe('NewName');
        expect(count($mock->sent))->toBe(2);
    });

    it('caches different attributes independently', function () {
        $mock = new MockTransport();
        $mock->addResponse(readResponseMsgString('Temperature'));
        $mock->addResponse(readResponseMsgString('Sensor description'));

        $client = setupConnectedClient($mock);
        setClientProperty($client, 'readMetadataCache', true);

        $dv1 = $client->read('i=1001', AttributeId::DisplayName);
        $dv2 = $client->read('i=1001', AttributeId::Description);

        expect(count($mock->sent))->toBe(2);
        expect($dv1->getValue())->toBe('Temperature');
        expect($dv2->getValue())->toBe('Sensor description');

        $dv3 = $client->read('i=1001', AttributeId::DisplayName);
        $dv4 = $client->read('i=1001', AttributeId::Description);
        expect(count($mock->sent))->toBe(2);
    });

    it('setReadMetadataCache returns self for fluent chaining on builder', function () {
        $builder = new PhpOpcua\Client\ClientBuilder();
        $result = $builder->setReadMetadataCache(true);

        expect($result)->toBe($builder);
    });

    it('refresh is ignored when cache is off', function () {
        $mock = new MockTransport();
        $mock->addResponse(readResponseMsgString('Name1'));
        $mock->addResponse(readResponseMsgString('Name2'));

        $client = setupConnectedClient($mock);

        $client->read('i=1001', AttributeId::DisplayName, refresh: true);
        $client->read('i=1001', AttributeId::DisplayName, refresh: true);

        expect(count($mock->sent))->toBe(2);
    });
});
