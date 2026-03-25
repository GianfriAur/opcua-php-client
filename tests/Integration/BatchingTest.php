<?php

declare(strict_types=1);

use Gianfriaur\OpcuaPhpClient\ClientBuilder;
use Gianfriaur\OpcuaPhpClient\Tests\Integration\Helpers\TestHelper;
use Gianfriaur\OpcuaPhpClient\Types\BuiltinType;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;
use Gianfriaur\OpcuaPhpClient\Types\StatusCode;

describe('ReadMulti batching', function () {

    it('readMulti works without batching', function () {
        $client = null;
        try {
            $client = (new ClientBuilder())
                ->connect(TestHelper::ENDPOINT_NO_SECURITY);

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

    it('readMulti with batchSize larger than items sends single request', function () {
        $client = null;
        try {
            $client = (new ClientBuilder())
                ->setBatchSize(100)
                ->connect(TestHelper::ENDPOINT_NO_SECURITY);

            $items = [
                ['nodeId' => NodeId::numeric(0, 2259)],
                ['nodeId' => NodeId::numeric(0, 2267)],
            ];

            $results = $client->readMulti($items);
            expect($results)->toHaveCount(2);
        } finally {
            TestHelper::safeDisconnect($client);
        }
    })->group('integration');

    it('readMulti with batchSize splits into multiple requests', function () {
        $client = null;
        try {
            $client = (new ClientBuilder())
                ->setBatchSize(2)
                ->connect(TestHelper::ENDPOINT_NO_SECURITY);

            $items = [
                ['nodeId' => NodeId::numeric(0, 2259)],
                ['nodeId' => NodeId::numeric(0, 2267)],
                ['nodeId' => NodeId::numeric(0, 2256)],
                ['nodeId' => NodeId::numeric(0, 2259)],
                ['nodeId' => NodeId::numeric(0, 2267)],
            ];

            $results = $client->readMulti($items);

            expect($results)->toHaveCount(5);
            foreach ($results as $dv) {
                expect(StatusCode::isBad($dv->getStatusCode()))->toBeFalse();
            }
        } finally {
            TestHelper::safeDisconnect($client);
        }
    })->group('integration');

    it('readMulti with batchSize=1 reads one node at a time', function () {
        $client = null;
        try {
            $client = (new ClientBuilder())
                ->setBatchSize(1)
                ->connect(TestHelper::ENDPOINT_NO_SECURITY);

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

    it('readMulti preserves result order across batches', function () {
        $client = null;
        try {
            $client = (new ClientBuilder())
                ->setBatchSize(2)
                ->connect(TestHelper::ENDPOINT_NO_SECURITY);

            $items = [
                ['nodeId' => NodeId::numeric(0, 2259)],
                ['nodeId' => NodeId::numeric(0, 2267)],
                ['nodeId' => NodeId::numeric(0, 2259)],
            ];

            $results = $client->readMulti($items);
            expect($results)->toHaveCount(3);

            expect($results[0]->getValue())->toBe($results[2]->getValue());
        } finally {
            TestHelper::safeDisconnect($client);
        }
    })->group('integration');

})->group('integration');

describe('WriteMulti batching', function () {

    it('writeMulti with batchSize splits into multiple requests', function () {
        $client = null;
        try {
            $client = (new ClientBuilder())
                ->setBatchSize(2)
                ->connect(TestHelper::ENDPOINT_NO_SECURITY);

            $nodeId = TestHelper::browseToNode($client, ['TestServer', 'DataTypes', 'Scalar', 'Int32Value']);

            $items = [
                ['nodeId' => $nodeId, 'value' => 10, 'type' => BuiltinType::Int32],
                ['nodeId' => $nodeId, 'value' => 20, 'type' => BuiltinType::Int32],
                ['nodeId' => $nodeId, 'value' => 30, 'type' => BuiltinType::Int32],
            ];

            $results = $client->writeMulti($items);

            expect($results)->toHaveCount(3);
            foreach ($results as $status) {
                expect(StatusCode::isGood($status))->toBeTrue();
            }
        } finally {
            TestHelper::safeDisconnect($client);
        }
    })->group('integration');

    it('writeMulti without batching works normally', function () {
        $client = null;
        try {
            $client = (new ClientBuilder())
                ->connect(TestHelper::ENDPOINT_NO_SECURITY);

            $nodeId = TestHelper::browseToNode($client, ['TestServer', 'DataTypes', 'Scalar', 'Int32Value']);

            $items = [
                ['nodeId' => $nodeId, 'value' => 42, 'type' => BuiltinType::Int32],
                ['nodeId' => $nodeId, 'value' => 99, 'type' => BuiltinType::Int32],
            ];

            $results = $client->writeMulti($items);
            expect($results)->toHaveCount(2);
        } finally {
            TestHelper::safeDisconnect($client);
        }
    })->group('integration');

})->group('integration');

describe('Server operation limits discovery', function () {

    it('discovers server MaxNodesPerRead after connect', function () {
        $client = null;
        try {
            $client = (new ClientBuilder())
                ->connect(TestHelper::ENDPOINT_NO_SECURITY);

            $limit = $client->getServerMaxNodesPerRead();

            expect($limit === null || is_int($limit))->toBeTrue();
        } finally {
            TestHelper::safeDisconnect($client);
        }
    })->group('integration');

    it('discovers server MaxNodesPerWrite after connect', function () {
        $client = null;
        try {
            $client = (new ClientBuilder())
                ->connect(TestHelper::ENDPOINT_NO_SECURITY);

            $limit = $client->getServerMaxNodesPerWrite();
            expect($limit === null || is_int($limit))->toBeTrue();
        } finally {
            TestHelper::safeDisconnect($client);
        }
    })->group('integration');

    it('server limits are reset after disconnect', function () {
        $client = (new ClientBuilder())
            ->connect(TestHelper::ENDPOINT_NO_SECURITY);
        $client->disconnect();

        expect($client->getServerMaxNodesPerRead())->toBeNull();
        expect($client->getServerMaxNodesPerWrite())->toBeNull();
    })->group('integration');

    it('setBatchSize overrides server limits', function () {
        $client = null;
        try {
            $client = (new ClientBuilder())
                ->setBatchSize(50)
                ->connect(TestHelper::ENDPOINT_NO_SECURITY);

            expect($client->getBatchSize())->toBe(50);

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

})->group('integration');

describe('Batching disabled after setBatchSize(0)', function () {

    it('readMulti sends all items in single request after disabling batching', function () {
        $client = null;
        try {
            $client = (new ClientBuilder())
                ->setBatchSize(2)
                ->setBatchSize(0) // disable
                ->connect(TestHelper::ENDPOINT_NO_SECURITY);

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

    it('setBatchSize(0) skips server operation limits discovery', function () {
        $client = null;
        try {
            $client = (new ClientBuilder())
                ->setBatchSize(0)
                ->connect(TestHelper::ENDPOINT_NO_SECURITY);

            expect($client->getServerMaxNodesPerRead())->toBeNull();
            expect($client->getServerMaxNodesPerWrite())->toBeNull();
            expect($client->getBatchSize())->toBe(0);
        } finally {
            TestHelper::safeDisconnect($client);
        }
    })->group('integration');

})->group('integration');
