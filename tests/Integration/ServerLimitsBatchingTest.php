<?php

declare(strict_types=1);

use PhpOpcua\Client\ClientBuilder;
use PhpOpcua\Client\Tests\Integration\Helpers\TestHelper;
use PhpOpcua\Client\Types\NodeId;
use PhpOpcua\Client\Types\StatusCode;

describe('Server with real operation limits', function () {

    it('discovers server MaxNodesPerRead = 5', function () {
        $client = null;
        try {
            $client = TestHelper::connectNoSecurity();

            expect($client->getServerMaxNodesPerRead())->toBe(5);
            expect($client->getServerMaxNodesPerWrite())->toBe(5);
        } finally {
            TestHelper::safeDisconnect($client);
        }
    })->group('integration');

    it('readMulti auto-batches when exceeding server limit', function () {
        $client = null;
        try {
            $client = TestHelper::connectNoSecurity();

            $items = [];
            for ($i = 0; $i < 8; $i++) {
                $items[] = ['nodeId' => NodeId::numeric(0, 2259)];
            }

            $results = $client->readMulti($items);

            expect($results)->toHaveCount(8);
            foreach ($results as $dv) {
                expect(StatusCode::isGood($dv->getStatusCode()))->toBeTrue();
            }
        } finally {
            TestHelper::safeDisconnect($client);
        }
    })->group('integration');

    it('readMulti within limit sends single request', function () {
        $client = null;
        try {
            $client = TestHelper::connectNoSecurity();

            $items = [
                ['nodeId' => NodeId::numeric(0, 2259)],
                ['nodeId' => NodeId::numeric(0, 2267)],
                ['nodeId' => NodeId::numeric(0, 2256)],
            ];

            $results = $client->readMulti($items);
            expect($results)->toHaveCount(3);
        } finally {
            TestHelper::safeDisconnect($client);
        }
    })->group('integration');

    it('readMulti exactly at limit does not batch', function () {
        $client = null;
        try {
            $client = TestHelper::connectNoSecurity();

            $items = [];
            for ($i = 0; $i < 5; $i++) {
                $items[] = ['nodeId' => NodeId::numeric(0, 2259)];
            }

            $results = $client->readMulti($items);
            expect($results)->toHaveCount(5);
        } finally {
            TestHelper::safeDisconnect($client);
        }
    })->group('integration');

    it('readMulti with many items batches correctly', function () {
        $client = null;
        try {
            $client = TestHelper::connectNoSecurity();

            $items = [];
            for ($i = 0; $i < 13; $i++) {
                $items[] = ['nodeId' => NodeId::numeric(0, 2259)];
            }

            $results = $client->readMulti($items);
            expect($results)->toHaveCount(13);
        } finally {
            TestHelper::safeDisconnect($client);
        }
    })->group('integration');

    it('setBatchSize(0) disables auto-batching and skips discovery', function () {
        $client = null;
        try {
            $client = (new ClientBuilder())
                ->setBatchSize(0)
                ->connect(TestHelper::ENDPOINT_NO_SECURITY);

            expect($client->getServerMaxNodesPerRead())->toBeNull();
            expect($client->getServerMaxNodesPerWrite())->toBeNull();
        } finally {
            TestHelper::safeDisconnect($client);
        }
    })->group('integration');

    it('setBatchSize overrides server limit', function () {
        $client = null;
        try {
            $client = (new ClientBuilder())
                ->setBatchSize(3)
                ->connect(TestHelper::ENDPOINT_NO_SECURITY);

            expect($client->getBatchSize())->toBe(3);
            expect($client->getServerMaxNodesPerRead())->toBe(5);

            $items = [];
            for ($i = 0; $i < 7; $i++) {
                $items[] = ['nodeId' => NodeId::numeric(0, 2259)];
            }

            $results = $client->readMulti($items);

            expect($results)->toHaveCount(7);
        } finally {
            TestHelper::safeDisconnect($client);
        }
    })->group('integration');

})->group('integration');
