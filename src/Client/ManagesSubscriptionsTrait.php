<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Client;

use Gianfriaur\OpcuaPhpClient\Encoding\BinaryDecoder;
use Gianfriaur\OpcuaPhpClient\Types\MonitoredItemResult;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;
use Gianfriaur\OpcuaPhpClient\Types\PublishResult;
use Gianfriaur\OpcuaPhpClient\Types\SubscriptionResult;

trait ManagesSubscriptionsTrait
{
    /**
     * @param float $publishingInterval
     * @param int $lifetimeCount
     * @param int $maxKeepAliveCount
     * @param int $maxNotificationsPerPublish
     * @param bool $publishingEnabled
     * @param int $priority
     * @return SubscriptionResult
     */
    public function createSubscription(float $publishingInterval = 500.0, int $lifetimeCount = 2400, int $maxKeepAliveCount = 10, int $maxNotificationsPerPublish = 0, bool $publishingEnabled = true, int $priority = 0): SubscriptionResult
    {
        return $this->executeWithRetry(function () use ($publishingInterval, $lifetimeCount, $maxKeepAliveCount, $maxNotificationsPerPublish, $publishingEnabled, $priority) {
            $this->ensureConnected();

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
            $decoder = $this->createDecoder($responseBody);

            return $this->subscriptionService->decodeCreateSubscriptionResponse($decoder);
        });
    }

    /**
     * @param int $subscriptionId
     * @param array<array{nodeId: NodeId, attributeId?: int, samplingInterval?: float, queueSize?: int, clientHandle?: int, monitoringMode?: int}> $items
     * @return MonitoredItemResult[]
     */
    public function createMonitoredItems(int $subscriptionId, array $items): array
    {
        return $this->executeWithRetry(function () use ($subscriptionId, $items) {
            $this->ensureConnected();

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
            $decoder = $this->createDecoder($responseBody);

            return $this->monitoredItemService->decodeCreateMonitoredItemsResponse($decoder);
        });
    }

    /**
     * @param int $subscriptionId
     * @param NodeId $nodeId
     * @param string[] $selectFields
     * @param int $clientHandle
     * @return MonitoredItemResult
     */
    public function createEventMonitoredItem(
        int    $subscriptionId,
        NodeId $nodeId,
        array  $selectFields = ['EventId', 'EventType', 'SourceName', 'Time', 'Message', 'Severity'],
        int    $clientHandle = 1,
    ): MonitoredItemResult
    {
        return $this->executeWithRetry(function () use ($subscriptionId, $nodeId, $selectFields, $clientHandle) {
            $this->ensureConnected();

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
            $decoder = $this->createDecoder($responseBody);

            $results = $this->monitoredItemService->decodeCreateMonitoredItemsResponse($decoder);

            return $results[0] ?? new MonitoredItemResult(0, 0, 0.0, 0);
        });
    }

    /**
     * @param int $subscriptionId
     * @param int[] $monitoredItemIds
     * @return int[]
     */
    public function deleteMonitoredItems(int $subscriptionId, array $monitoredItemIds): array
    {
        return $this->executeWithRetry(function () use ($subscriptionId, $monitoredItemIds) {
            $this->ensureConnected();

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
            $decoder = $this->createDecoder($responseBody);

            return $this->monitoredItemService->decodeDeleteMonitoredItemsResponse($decoder);
        });
    }

    /**
     * @param int $subscriptionId
     * @return int
     */
    public function deleteSubscription(int $subscriptionId): int
    {
        return $this->executeWithRetry(function () use ($subscriptionId) {
            $this->ensureConnected();

            $requestId = $this->nextRequestId();
            $request = $this->subscriptionService->encodeDeleteSubscriptionsRequest(
                $requestId,
                $this->authenticationToken,
                [$subscriptionId],
            );
            $this->transport->send($request);

            $response = $this->transport->receive();
            $responseBody = $this->unwrapResponse($response);
            $decoder = $this->createDecoder($responseBody);

            $results = $this->subscriptionService->decodeDeleteSubscriptionsResponse($decoder);

            return $results[0] ?? 0;
        });
    }

    /**
     * @param array<array{subscriptionId: int, sequenceNumber: int}> $acknowledgements
     * @return PublishResult
     */
    public function publish(array $acknowledgements = []): PublishResult
    {
        return $this->executeWithRetry(function () use ($acknowledgements) {
            $this->ensureConnected();

            $requestId = $this->nextRequestId();
            $request = $this->publishService->encodePublishRequest(
                $requestId,
                $this->authenticationToken,
                $acknowledgements,
            );
            $this->transport->send($request);

            $response = $this->transport->receive();
            $responseBody = $this->unwrapResponse($response);
            $decoder = $this->createDecoder($responseBody);

            return $this->publishService->decodePublishResponse($decoder);
        });
    }
}
