<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Client;

use Gianfriaur\OpcuaPhpClient\Encoding\BinaryDecoder;
use Gianfriaur\OpcuaPhpClient\Exception\ConnectionException;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;

trait ManagesSubscriptionsTrait
{
    /**
     * @param float $publishingInterval
     * @param int $lifetimeCount
     * @param int $maxKeepAliveCount
     * @param int $maxNotificationsPerPublish
     * @param bool $publishingEnabled
     * @param int $priority
     * @return array{subscriptionId: int, revisedPublishingInterval: float, revisedLifetimeCount: int, revisedMaxKeepAliveCount: int}
     */
    public function createSubscription(
        float $publishingInterval = 500.0,
        int $lifetimeCount = 2400,
        int $maxKeepAliveCount = 10,
        int $maxNotificationsPerPublish = 0,
        bool $publishingEnabled = true,
        int $priority = 0,
    ): array {
        if ($this->subscriptionService === null || $this->authenticationToken === null) {
            throw new ConnectionException('Not connected');
        }

        $requestId = $this->nextRequestId();
        $request = $this->subscriptionService->encodeCreateSubscriptionRequest(
            $requestId,
            $this->authenticationToken,
            $publishingInterval,
            $lifetimeCount,
            $maxKeepAliveCount,
            $maxNotificationsPerPublish,
            $publishingEnabled,
            $priority,
        );
        $this->transport->send($request);

        $response = $this->transport->receive();
        $responseBody = $this->unwrapResponse($response);
        $decoder = new BinaryDecoder($responseBody);

        return $this->subscriptionService->decodeCreateSubscriptionResponse($decoder);
    }

    /**
     * @param int $subscriptionId
     * @param array<array{nodeId: NodeId, attributeId?: int, samplingInterval?: float, queueSize?: int, clientHandle?: int, monitoringMode?: int}> $items
     * @return array<array{statusCode: int, monitoredItemId: int, revisedSamplingInterval: float, revisedQueueSize: int}>
     */
    public function createMonitoredItems(
        int $subscriptionId,
        array $items,
    ): array {
        if ($this->monitoredItemService === null || $this->authenticationToken === null) {
            throw new ConnectionException('Not connected');
        }

        $requestId = $this->nextRequestId();
        $request = $this->monitoredItemService->encodeCreateMonitoredItemsRequest(
            $requestId,
            $this->authenticationToken,
            $subscriptionId,
            $items,
        );
        $this->transport->send($request);

        $response = $this->transport->receive();
        $responseBody = $this->unwrapResponse($response);
        $decoder = new BinaryDecoder($responseBody);

        return $this->monitoredItemService->decodeCreateMonitoredItemsResponse($decoder);
    }

    /**
     * @param int $subscriptionId
     * @param NodeId $nodeId
     * @param string[] $selectFields
     * @param int $clientHandle
     * @return array{statusCode: int, monitoredItemId: int, revisedSamplingInterval: float, revisedQueueSize: int}
     */
    public function createEventMonitoredItem(
        int $subscriptionId,
        NodeId $nodeId,
        array $selectFields = ['EventId', 'EventType', 'SourceName', 'Time', 'Message', 'Severity'],
        int $clientHandle = 1,
    ): array {
        if ($this->monitoredItemService === null || $this->authenticationToken === null) {
            throw new ConnectionException('Not connected');
        }

        $requestId = $this->nextRequestId();
        $request = $this->monitoredItemService->encodeCreateEventMonitoredItemRequest(
            $requestId,
            $this->authenticationToken,
            $subscriptionId,
            $nodeId,
            $selectFields,
            $clientHandle,
        );
        $this->transport->send($request);

        $response = $this->transport->receive();
        $responseBody = $this->unwrapResponse($response);
        $decoder = new BinaryDecoder($responseBody);

        $results = $this->monitoredItemService->decodeCreateMonitoredItemsResponse($decoder);

        return $results[0] ?? ['statusCode' => 0, 'monitoredItemId' => 0, 'revisedSamplingInterval' => 0.0, 'revisedQueueSize' => 0];
    }

    /**
     * @param int $subscriptionId
     * @param int[] $monitoredItemIds
     * @return int[]
     */
    public function deleteMonitoredItems(int $subscriptionId, array $monitoredItemIds): array
    {
        if ($this->monitoredItemService === null || $this->authenticationToken === null) {
            throw new ConnectionException('Not connected');
        }

        $requestId = $this->nextRequestId();
        $request = $this->monitoredItemService->encodeDeleteMonitoredItemsRequest(
            $requestId,
            $this->authenticationToken,
            $subscriptionId,
            $monitoredItemIds,
        );
        $this->transport->send($request);

        $response = $this->transport->receive();
        $responseBody = $this->unwrapResponse($response);
        $decoder = new BinaryDecoder($responseBody);

        return $this->monitoredItemService->decodeDeleteMonitoredItemsResponse($decoder);
    }

    /**
     * @param int $subscriptionId
     * @return int
     */
    public function deleteSubscription(int $subscriptionId): int
    {
        if ($this->subscriptionService === null || $this->authenticationToken === null) {
            throw new ConnectionException('Not connected');
        }

        $requestId = $this->nextRequestId();
        $request = $this->subscriptionService->encodeDeleteSubscriptionsRequest(
            $requestId,
            $this->authenticationToken,
            [$subscriptionId],
        );
        $this->transport->send($request);

        $response = $this->transport->receive();
        $responseBody = $this->unwrapResponse($response);
        $decoder = new BinaryDecoder($responseBody);

        $results = $this->subscriptionService->decodeDeleteSubscriptionsResponse($decoder);

        return $results[0] ?? 0;
    }

    /**
     * @param array<array{subscriptionId: int, sequenceNumber: int}> $acknowledgements
     * @return array{subscriptionId: int, sequenceNumber: int, moreNotifications: bool, notifications: array, availableSequenceNumbers: int[]}
     */
    public function publish(array $acknowledgements = []): array
    {
        if ($this->publishService === null || $this->authenticationToken === null) {
            throw new ConnectionException('Not connected');
        }

        $requestId = $this->nextRequestId();
        $request = $this->publishService->encodePublishRequest(
            $requestId,
            $this->authenticationToken,
            $acknowledgements,
        );
        $this->transport->send($request);

        $response = $this->transport->receive();
        $responseBody = $this->unwrapResponse($response);
        $decoder = new BinaryDecoder($responseBody);

        return $this->publishService->decodePublishResponse($decoder);
    }
}
