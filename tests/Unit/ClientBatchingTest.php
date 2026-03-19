<?php

declare(strict_types=1);

use Gianfriaur\OpcuaPhpClient\Client;
use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

describe('Batching configuration', function () {

    it('getBatchSize returns null by default', function () {
        $client = new Client();
        expect($client->getBatchSize())->toBeNull();
    });

    it('setBatchSize returns self for fluent chaining', function () {
        $client = new Client();
        $result = $client->setBatchSize(100);
        expect($result)->toBe($client);
    });

    it('setBatchSize stores the value', function () {
        $client = new Client();
        $client->setBatchSize(50);
        expect($client->getBatchSize())->toBe(50);
    });

    it('setBatchSize to 0 disables batching explicitly', function () {
        $client = new Client();
        $client->setBatchSize(100);
        expect($client->getBatchSize())->toBe(100);

        $client->setBatchSize(0);
        expect($client->getBatchSize())->toBe(0);
    });

    it('setBatchSize can be updated multiple times', function () {
        $client = new Client();
        $client->setBatchSize(10);
        expect($client->getBatchSize())->toBe(10);

        $client->setBatchSize(200);
        expect($client->getBatchSize())->toBe(200);
    });

    it('supports fluent chaining with other config methods', function () {
        $client = new Client();
        $result = $client
            ->setTimeout(10.0)
            ->setBatchSize(50)
            ->setAutoRetry(2);
        expect($result)->toBe($client);
        expect($client->getBatchSize())->toBe(50);
        expect($client->getTimeout())->toBe(10.0);
        expect($client->getAutoRetry())->toBe(2);
    });

    it('implements OpcUaClientInterface batching methods', function () {
        $reflection = new ReflectionClass(OpcUaClientInterface::class);
        expect($reflection->hasMethod('setBatchSize'))->toBeTrue();
        expect($reflection->hasMethod('getBatchSize'))->toBeTrue();
        expect($reflection->hasMethod('getServerMaxNodesPerRead'))->toBeTrue();
        expect($reflection->hasMethod('getServerMaxNodesPerWrite'))->toBeTrue();
    });

    it('server limits are null before connect', function () {
        $client = new Client();
        expect($client->getServerMaxNodesPerRead())->toBeNull();
        expect($client->getServerMaxNodesPerWrite())->toBeNull();
    });
});
