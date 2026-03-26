<?php

declare(strict_types=1);

use PhpOpcua\Client\ClientBuilder;
use PhpOpcua\Client\ClientBuilderInterface;

describe('Auto-retry configuration', function () {

    it('getAutoRetry defaults to 0', function () {
        $builder = new ClientBuilder();
        expect($builder->getAutoRetry())->toBe(0);
    });

    it('setAutoRetry returns self for fluent chaining', function () {
        $builder = new ClientBuilder();
        $result = $builder->setAutoRetry(3);
        expect($result)->toBe($builder);
    });

    it('setAutoRetry overrides the default value', function () {
        $builder = new ClientBuilder();
        $builder->setAutoRetry(5);
        expect($builder->getAutoRetry())->toBe(5);
    });

    it('setAutoRetry to 0 disables retry', function () {
        $builder = new ClientBuilder();
        $builder->setAutoRetry(0);
        expect($builder->getAutoRetry())->toBe(0);
    });

    it('setAutoRetry can be updated multiple times', function () {
        $builder = new ClientBuilder();
        $builder->setAutoRetry(2);
        expect($builder->getAutoRetry())->toBe(2);

        $builder->setAutoRetry(10);
        expect($builder->getAutoRetry())->toBe(10);

        $builder->setAutoRetry(0);
        expect($builder->getAutoRetry())->toBe(0);
    });

    it('supports fluent chaining with setTimeout', function () {
        $builder = new ClientBuilder();
        $result = $builder
            ->setTimeout(10.0)
            ->setAutoRetry(3);
        expect($result)->toBe($builder);
        expect($builder->getAutoRetry())->toBe(3);
        expect($builder->getTimeout())->toBe(10.0);
    });

    it('implements ClientBuilderInterface auto-retry methods', function () {
        $reflection = new ReflectionClass(ClientBuilderInterface::class);
        expect($reflection->hasMethod('setAutoRetry'))->toBeTrue();
        expect($reflection->hasMethod('getAutoRetry'))->toBeTrue();
    });
});
