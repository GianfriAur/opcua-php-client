<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Client;

use DateTimeImmutable;
use Gianfriaur\OpcuaPhpClient\Encoding\BinaryDecoder;
use Gianfriaur\OpcuaPhpClient\Types\DataValue;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;

/**
 * Provides historical data access operations for reading raw, processed, and at-time node values.
 */
trait ManagesHistoryTrait
{
    /**
     * Read raw historical data for a node.
     *
     * @param NodeId|string $nodeId The node to read history from.
     * @param ?DateTimeImmutable $startTime Start of the time range, or null for open-ended.
     * @param ?DateTimeImmutable $endTime End of the time range, or null for open-ended.
     * @param int $numValuesPerNode Maximum values to return (0 = server default).
     * @param bool $returnBounds Whether to include bounding values.
     * @return DataValue[]
     *
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\InvalidNodeIdException If a string parameter cannot be parsed as a NodeId.
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ConnectionException If the connection is lost during the request.
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ServiceException If the server returns an error response.
     */
    public function historyReadRaw(
        NodeId|string      $nodeId,
        ?DateTimeImmutable $startTime = null,
        ?DateTimeImmutable $endTime = null,
        int                $numValuesPerNode = 0,
        bool               $returnBounds = false,
    ): array
    {
        $nodeId = $this->resolveNodeIdParam($nodeId);
        return $this->executeWithRetry(function () use ($nodeId, $startTime, $endTime, $numValuesPerNode, $returnBounds) {
            $this->ensureConnected();

            $requestId = $this->nextRequestId();
            $request = $this->historyReadService->encodeHistoryReadRawRequest(
                $requestId,
                $this->authenticationToken,
                $nodeId,
                $startTime,
                $endTime,
                $numValuesPerNode,
                $returnBounds,
            );
            $this->transport->send($request);

            $response = $this->transport->receive();
            $responseBody = $this->unwrapResponse($response);
            $decoder = $this->createDecoder($responseBody);

            return $this->historyReadService->decodeHistoryReadResponse($decoder);
        });
    }

    /**
     * Read processed (aggregated) historical data for a node.
     *
     * @param NodeId|string $nodeId The node to read history from.
     * @param DateTimeImmutable $startTime Start of the time range.
     * @param DateTimeImmutable $endTime End of the time range.
     * @param float $processingInterval Aggregation interval in milliseconds.
     * @param NodeId $aggregateType The aggregate function NodeId (e.g. Average, Count).
     * @return DataValue[]
     *
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\InvalidNodeIdException If a string parameter cannot be parsed as a NodeId.
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ConnectionException If the connection is lost during the request.
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ServiceException If the server returns an error response.
     */
    public function historyReadProcessed(
        NodeId|string     $nodeId,
        DateTimeImmutable $startTime,
        DateTimeImmutable $endTime,
        float             $processingInterval,
        NodeId            $aggregateType,
    ): array
    {
        $nodeId = $this->resolveNodeIdParam($nodeId);
        return $this->executeWithRetry(function () use ($nodeId, $startTime, $endTime, $processingInterval, $aggregateType) {
            $this->ensureConnected();

            $requestId = $this->nextRequestId();
            $request = $this->historyReadService->encodeHistoryReadProcessedRequest(
                $requestId,
                $this->authenticationToken,
                $nodeId,
                $startTime,
                $endTime,
                $processingInterval,
                $aggregateType,
            );
            $this->transport->send($request);

            $response = $this->transport->receive();
            $responseBody = $this->unwrapResponse($response);
            $decoder = $this->createDecoder($responseBody);

            return $this->historyReadService->decodeHistoryReadResponse($decoder);
        });
    }

    /**
     * Read historical data at specific timestamps for a node.
     *
     * @param NodeId|string $nodeId The node to read history from.
     * @param DateTimeImmutable[] $timestamps The exact timestamps to retrieve values for.
     * @return DataValue[]
     *
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\InvalidNodeIdException If a string parameter cannot be parsed as a NodeId.
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ConnectionException If the connection is lost during the request.
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ServiceException If the server returns an error response.
     */
    public function historyReadAtTime(
        NodeId|string $nodeId,
        array         $timestamps,
    ): array
    {
        $nodeId = $this->resolveNodeIdParam($nodeId);
        return $this->executeWithRetry(function () use ($nodeId, $timestamps) {
            $this->ensureConnected();

            $requestId = $this->nextRequestId();
            $request = $this->historyReadService->encodeHistoryReadAtTimeRequest(
                $requestId,
                $this->authenticationToken,
                $nodeId,
                $timestamps,
            );
            $this->transport->send($request);

            $response = $this->transport->receive();
            $responseBody = $this->unwrapResponse($response);
            $decoder = $this->createDecoder($responseBody);

            return $this->historyReadService->decodeHistoryReadResponse($decoder);
        });
    }
}
