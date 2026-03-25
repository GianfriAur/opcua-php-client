<?php

declare(strict_types=1);

use Gianfriaur\OpcuaPhpClient\ClientBuilder;
use Gianfriaur\OpcuaPhpClient\ClientBuilderInterface;

describe('Batching configuration', function () {

    it('getBatchSize returns null by default', function () {
        $builder = new ClientBuilder();
        expect($builder->getBatchSize())->toBeNull();
    });

    it('setBatchSize returns self for fluent chaining', function () {
        $builder = new ClientBuilder();
        $result = $builder->setBatchSize(100);
        expect($result)->toBe($builder);
    });

    it('setBatchSize stores the value', function () {
        $builder = new ClientBuilder();
        $builder->setBatchSize(50);
        expect($builder->getBatchSize())->toBe(50);
    });

    it('setBatchSize to 0 disables batching explicitly', function () {
        $builder = new ClientBuilder();
        $builder->setBatchSize(100);
        expect($builder->getBatchSize())->toBe(100);

        $builder->setBatchSize(0);
        expect($builder->getBatchSize())->toBe(0);
    });

    it('setBatchSize can be updated multiple times', function () {
        $builder = new ClientBuilder();
        $builder->setBatchSize(10);
        expect($builder->getBatchSize())->toBe(10);

        $builder->setBatchSize(200);
        expect($builder->getBatchSize())->toBe(200);
    });

    it('supports fluent chaining with other config methods', function () {
        $builder = new ClientBuilder();
        $result = $builder
            ->setTimeout(10.0)
            ->setBatchSize(50)
            ->setAutoRetry(2);
        expect($result)->toBe($builder);
        expect($builder->getBatchSize())->toBe(50);
        expect($builder->getTimeout())->toBe(10.0);
        expect($builder->getAutoRetry())->toBe(2);
    });

    it('implements ClientBuilderInterface batching methods', function () {
        $reflection = new ReflectionClass(ClientBuilderInterface::class);
        expect($reflection->hasMethod('setBatchSize'))->toBeTrue();
        expect($reflection->hasMethod('getBatchSize'))->toBeTrue();
    });
});
