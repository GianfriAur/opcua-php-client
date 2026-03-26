<?php

declare(strict_types=1);

use PhpOpcua\Client\ClientBuilder;
use PhpOpcua\Client\Types\ConnectionState;

describe('ConnectionState enum', function () {

    it('has three cases', function () {
        $cases = ConnectionState::cases();
        expect($cases)->toHaveCount(3);
        expect(array_map(fn ($c) => $c->name, $cases))
            ->toContain('Disconnected', 'Connected', 'Broken');
    });
});

describe('ClientBuilder connection-related configuration', function () {

    it('getAutoRetry defaults to 0', function () {
        $builder = new ClientBuilder();
        expect($builder->getAutoRetry())->toBe(0);
    });

    it('setAutoRetry returns self for chaining', function () {
        $builder = new ClientBuilder();
        $result = $builder->setAutoRetry(3);
        expect($result)->toBe($builder);
    });

    it('setAutoRetry overrides the default', function () {
        $builder = new ClientBuilder();
        $builder->setAutoRetry(5);
        expect($builder->getAutoRetry())->toBe(5);
    });

    it('setAutoRetry to 0 disables retry', function () {
        $builder = new ClientBuilder();
        $builder->setAutoRetry(0);
        expect($builder->getAutoRetry())->toBe(0);
    });
});
