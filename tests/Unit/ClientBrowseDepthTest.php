<?php

declare(strict_types=1);

use PhpOpcua\Client\ClientBuilder;
use PhpOpcua\Client\ClientBuilderInterface;

describe('Browse depth configuration', function () {

    it('getDefaultBrowseMaxDepth returns 10 by default', function () {
        $builder = new ClientBuilder();
        expect($builder->getDefaultBrowseMaxDepth())->toBe(10);
    });

    it('setDefaultBrowseMaxDepth returns self for fluent chaining', function () {
        $builder = new ClientBuilder();
        $result = $builder->setDefaultBrowseMaxDepth(5);
        expect($result)->toBe($builder);
    });

    it('setDefaultBrowseMaxDepth stores the value', function () {
        $builder = new ClientBuilder();
        $builder->setDefaultBrowseMaxDepth(20);
        expect($builder->getDefaultBrowseMaxDepth())->toBe(20);
    });

    it('setDefaultBrowseMaxDepth accepts -1 for unlimited', function () {
        $builder = new ClientBuilder();
        $builder->setDefaultBrowseMaxDepth(-1);
        expect($builder->getDefaultBrowseMaxDepth())->toBe(-1);
    });

    it('setDefaultBrowseMaxDepth can be updated multiple times', function () {
        $builder = new ClientBuilder();
        $builder->setDefaultBrowseMaxDepth(5);
        expect($builder->getDefaultBrowseMaxDepth())->toBe(5);

        $builder->setDefaultBrowseMaxDepth(50);
        expect($builder->getDefaultBrowseMaxDepth())->toBe(50);
    });

    it('supports fluent chaining with other config methods', function () {
        $builder = new ClientBuilder();
        $result = $builder
            ->setTimeout(10.0)
            ->setDefaultBrowseMaxDepth(20)
            ->setAutoRetry(2);
        expect($result)->toBe($builder);
        expect($builder->getDefaultBrowseMaxDepth())->toBe(20);
    });

    it('implements ClientBuilderInterface browse depth methods', function () {
        $reflection = new ReflectionClass(ClientBuilderInterface::class);
        expect($reflection->hasMethod('setDefaultBrowseMaxDepth'))->toBeTrue();
        expect($reflection->hasMethod('getDefaultBrowseMaxDepth'))->toBeTrue();
    });
});
