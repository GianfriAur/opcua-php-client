<?php

declare(strict_types=1);

use Gianfriaur\OpcuaPhpClient\ClientBuilder;
use Gianfriaur\OpcuaPhpClient\ClientBuilderInterface;
use Gianfriaur\OpcuaPhpClient\Transport\TcpTransport;

describe('Client timeout configuration', function () {

    it('has default timeout matching TcpTransport default', function () {
        $builder = new ClientBuilder();
        expect($builder->getTimeout())->toBe(TcpTransport::DEFAULT_TIMEOUT);
    });

    it('setTimeout updates the timeout value', function () {
        $builder = new ClientBuilder();
        $builder->setTimeout(15.0);
        expect($builder->getTimeout())->toBe(15.0);
    });

    it('setTimeout returns self for fluent chaining', function () {
        $builder = new ClientBuilder();
        $result = $builder->setTimeout(10.0);
        expect($result)->toBe($builder);
    });

    it('supports fluent chaining with other configuration methods', function () {
        $builder = new ClientBuilder();
        $result = $builder
            ->setTimeout(10.0)
            ->setSecurityPolicy(Gianfriaur\OpcuaPhpClient\Security\SecurityPolicy::None)
            ->setSecurityMode(Gianfriaur\OpcuaPhpClient\Security\SecurityMode::None);
        expect($result)->toBe($builder);
        expect($builder->getTimeout())->toBe(10.0);
    });

    it('accepts fractional seconds', function () {
        $builder = new ClientBuilder();
        $builder->setTimeout(0.5);
        expect($builder->getTimeout())->toBe(0.5);
    });

    it('can be updated multiple times', function () {
        $builder = new ClientBuilder();
        $builder->setTimeout(10.0);
        expect($builder->getTimeout())->toBe(10.0);

        $builder->setTimeout(30.0);
        expect($builder->getTimeout())->toBe(30.0);
    });

    it('implements ClientBuilderInterface timeout methods', function () {
        $builder = new ClientBuilder();
        expect($builder)->toBeInstanceOf(ClientBuilderInterface::class);

        $reflection = new ReflectionClass(ClientBuilderInterface::class);
        expect($reflection->hasMethod('setTimeout'))->toBeTrue();
        expect($reflection->hasMethod('getTimeout'))->toBeTrue();
    });
});
