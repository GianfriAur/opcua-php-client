<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Client;

use Gianfriaur\OpcuaPhpClient\Encoding\BinaryDecoder;
use Gianfriaur\OpcuaPhpClient\Types\BuiltinType;
use Gianfriaur\OpcuaPhpClient\Types\DataValue;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;
use Gianfriaur\OpcuaPhpClient\Types\CallResult;
use Gianfriaur\OpcuaPhpClient\Types\Variant;

/**
 * Provides read, write, and method call operations for OPC UA node attributes.
 */
trait ManagesReadWriteTrait
{
    /**
     * Read a single attribute from a node.
     *
     * @param NodeId $nodeId The node to read.
     * @param int $attributeId The attribute to read (default 13 = Value).
     * @return DataValue
     *
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ConnectionException If the connection is lost during the request.
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ServiceException If the server returns an error response.
     *
     * @see DataValue
     */
    public function read(NodeId $nodeId, int $attributeId = 13): DataValue
    {
        return $this->executeWithRetry(function () use ($nodeId, $attributeId) {
            $this->ensureConnected();

            $requestId = $this->nextRequestId();
            $request = $this->readService->encodeReadRequest($requestId, $nodeId, $this->authenticationToken, $attributeId);
            $this->transport->send($request);

            $response = $this->transport->receive();
            $responseBody = $this->unwrapResponse($response);
            $decoder = $this->createDecoder($responseBody);

            return $this->readService->decodeReadResponse($decoder);
        });
    }

    /**
     * Read multiple attributes from one or more nodes in a single request.
     *
     * Results are automatically batched if the number of items exceeds the effective batch size.
     *
     * @param array<array{nodeId: NodeId, attributeId?: int}> $items Items to read.
     * @return DataValue[]
     *
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ConnectionException If the connection is lost during the request.
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ServiceException If the server returns an error response.
     */
    public function readMulti(array $readItems): array
    {
        $batchSize = $this->getEffectiveReadBatchSize();
        if ($batchSize !== null && count($readItems) > $batchSize) {
            return $this->readMultiBatched($readItems, $batchSize);
        }

        return $this->readMultiRaw($readItems);
    }

    /**
     * @param array<array{nodeId: NodeId, attributeId?: int}> $items
     * @return DataValue[]
     */
    private function readMultiRaw(array $items): array
    {
        return $this->executeWithRetry(function () use ($items) {
            $this->ensureConnected();

            $requestId = $this->nextRequestId();
            $request = $this->readService->encodeReadMultiRequest($requestId, $items, $this->authenticationToken);
            $this->transport->send($request);

            $response = $this->transport->receive();
            $responseBody = $this->unwrapResponse($response);
            $decoder = $this->createDecoder($responseBody);

            return $this->readService->decodeReadMultiResponse($decoder);
        });
    }

    /**
     * @param array<array{nodeId: NodeId, attributeId?: int}> $items
     * @param int $batchSize
     * @return DataValue[]
     */
    private function readMultiBatched(array $items, int $batchSize): array
    {
        $results = [];
        foreach (array_chunk($items, $batchSize) as $batch) {
            $batchResults = $this->readMultiRaw($batch);
            array_push($results, ...$batchResults);
        }

        return $results;
    }

    /**
     * Write a value to a node attribute.
     *
     * @param NodeId $nodeId The node to write to.
     * @param mixed $value The value to write.
     * @param BuiltinType $type The OPC UA built-in type of the value.
     * @return int The OPC UA status code for the write result.
     *
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ConnectionException If the connection is lost during the request.
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ServiceException If the server returns an error response.
     */
    public function write(NodeId $nodeId, mixed $value, BuiltinType $type): int
    {
        return $this->executeWithRetry(function () use ($nodeId, $value, $type) {
            $this->ensureConnected();

            $variant = new Variant($type, $value);
            $dataValue = new DataValue($variant);

            $requestId = $this->nextRequestId();
            $request = $this->writeService->encodeWriteRequest($requestId, $nodeId, $dataValue, $this->authenticationToken);
            $this->transport->send($request);

            $response = $this->transport->receive();
            $responseBody = $this->unwrapResponse($response);
            $decoder = $this->createDecoder($responseBody);

            $results = $this->writeService->decodeWriteResponse($decoder);

            return $results[0] ?? 0;
        });
    }

    /**
     * Write multiple values to one or more nodes in a single request.
     *
     * Results are automatically batched if the number of items exceeds the effective batch size.
     *
     * @param array<array{nodeId: NodeId, value: mixed, type: BuiltinType, attributeId?: int}> $items Items to write.
     * @return int[] OPC UA status codes for each write result.
     *
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ConnectionException If the connection is lost during the request.
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ServiceException If the server returns an error response.
     */
    public function writeMulti(array $writeItems): array
    {
        $batchSize = $this->getEffectiveWriteBatchSize();
        if ($batchSize !== null && count($writeItems) > $batchSize) {
            return $this->writeMultiBatched($writeItems, $batchSize);
        }

        return $this->executeWithRetry(function () use ($writeItems) {
            $this->ensureConnected();

            $writeItems = $this->prepareWriteItems($writeItems);

            $requestId = $this->nextRequestId();
            $request = $this->writeService->encodeWriteMultiRequest($requestId, $writeItems, $this->authenticationToken);
            $this->transport->send($request);

            $response = $this->transport->receive();
            $responseBody = $this->unwrapResponse($response);
            $decoder = $this->createDecoder($responseBody);

            return $this->writeService->decodeWriteResponse($decoder);
        });
    }

    /**
     * @param array<array{nodeId: NodeId, value: mixed, type: BuiltinType, attributeId?: int}> $items
     * @param int $batchSize
     * @return int[]
     */
    private function writeMultiBatched(array $items, int $batchSize): array
    {
        $results = [];
        foreach (array_chunk($items, $batchSize) as $batch) {
            $batchResults = $this->executeWithRetry(function () use ($batch) {
                $this->ensureConnected();

                $writeItems = $this->prepareWriteItems($batch);

                $requestId = $this->nextRequestId();
                $request = $this->writeService->encodeWriteMultiRequest($requestId, $writeItems, $this->authenticationToken);
                $this->transport->send($request);

                $response = $this->transport->receive();
                $responseBody = $this->unwrapResponse($response);
                $decoder = $this->createDecoder($responseBody);

                return $this->writeService->decodeWriteResponse($decoder);
            });
            array_push($results, ...$batchResults);
        }

        return $results;
    }

    /**
     * @param array<array{nodeId: NodeId, value: mixed, type: BuiltinType, attributeId?: int}> $items
     * @return array<array{nodeId: NodeId, dataValue: DataValue, attributeId: int}>
     */
    private function prepareWriteItems(array $items): array
    {
        $writeItems = [];
        foreach ($items as $item) {
            $variant = new Variant($item['type'], $item['value']);
            $writeItems[] = [
                'nodeId' => $item['nodeId'],
                'dataValue' => new DataValue($variant),
                'attributeId' => $item['attributeId'] ?? 13,
            ];
        }

        return $writeItems;
    }

    /**
     * Call a method on an object node.
     *
     * @param NodeId $objectId The object node that owns the method.
     * @param NodeId $methodId The method node to invoke.
     * @param Variant[] $inputArguments Input arguments for the method call.
     * @return CallResult
     *
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ConnectionException If the connection is lost during the request.
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ServiceException If the server returns an error response.
     *
     * @see CallResult
     */
    public function call(NodeId $objectId, NodeId $methodId, array $inputArguments = []): CallResult
    {
        return $this->executeWithRetry(function () use ($objectId, $methodId, $inputArguments) {
            $this->ensureConnected();

            $requestId = $this->nextRequestId();
            $request = $this->callService->encodeCallRequest(
                $requestId,
                $objectId,
                $methodId,
                $inputArguments,
                $this->authenticationToken,
            );
            $this->transport->send($request);

            $response = $this->transport->receive();
            $responseBody = $this->unwrapResponse($response);
            $decoder = $this->createDecoder($responseBody);

            return $this->callService->decodeCallResponse($decoder);
        });
    }
}
