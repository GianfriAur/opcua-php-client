<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Client;

use Gianfriaur\OpcuaPhpClient\Encoding\BinaryDecoder;
use Gianfriaur\OpcuaPhpClient\Types\MonitoredItemResult;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;
use Gianfriaur\OpcuaPhpClient\Types\PublishResult;
use Gianfriaur\OpcuaPhpClient\Types\SubscriptionResult;

/**
 * Provides subscription and monitored item management for OPC UA data change and event notifications.
 */
trait ManagesSubscriptionsTrait
{
    /**
     * Create a subscription for receiving data change or event notifications.
     *
     * @param float $publishingInterval Requested publishing interval in milliseconds.
     * @param int $lifetimeCount Requested lifetime count (number of publishing intervals before expiry).
     * @param int $maxKeepAliveCount Maximum keep-alive count.
     * @param int $maxNotificationsPerPublish Maximum notifications per publish response (0 = unlimited).
     * @param bool $publishingEnabled Whether publishing is initially enabled.
     * @param int $priority Relative priority of the subscription.
     * @return SubscriptionResult
     *
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ConnectionException If the connection is lost during the request.
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ServiceException If the server returns an error response.
     *
     * @see SubscriptionResult
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
     * Create monitored items within an existing subscription for data change notifications.
     *
     * @param int $subscriptionId The subscription to add items to.
     * @param array<array{nodeId: NodeId|string, attributeId?: int, samplingInterval?: float, queueSize?: int, clientHandle?: int, monitoringMode?: int}> $items Items to monitor.
     * @return MonitoredItemResult[]
     *
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\InvalidNodeIdException If a string parameter cannot be parsed as a NodeId.
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ConnectionException If the connection is lost during the request.
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ServiceException If the server returns an error response.
     *
     * @see MonitoredItemResult
     */
    public function createMonitoredItems(int $subscriptionId, array $monitoredItems): array
    {
        foreach ($monitoredItems as &$item) {
            if (isset($item['nodeId']) && is_string($item['nodeId'])) {
                $item['nodeId'] = NodeId::parse($item['nodeId']);
            }
        }
        unset($item);

        return $this->executeWithRetry(function () use ($subscriptionId, $monitoredItems) {
            $this->ensureConnected();

            $requestId = $this->nextRequestId();
            $request = $this->monitoredItemService->encodeCreateMonitoredItemsRequest(
                $requestId,
                $this->authenticationToken,
                $subscriptionId,
                $monitoredItems,
            );
            $this->transport->send($request);

            $response = $this->transport->receive();
            $responseBody = $this->unwrapResponse($response);
            $decoder = $this->createDecoder($responseBody);

            return $this->monitoredItemService->decodeCreateMonitoredItemsResponse($decoder);
        });
    }

    /**
     * Create a single event-based monitored item within an existing subscription.
     *
     * @param int $subscriptionId The subscription to add the item to.
     * @param NodeId|string $nodeId The node to monitor for events.
     * @param string[] $selectFields Event fields to include in notifications.
     * @param int $clientHandle Client-assigned handle for correlating notifications.
     * @return MonitoredItemResult
     *
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\InvalidNodeIdException If a string parameter cannot be parsed as a NodeId.
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ConnectionException If the connection is lost during the request.
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ServiceException If the server returns an error response.
     *
     * @see MonitoredItemResult
     */
    public function createEventMonitoredItem(
        int           $subscriptionId,
        NodeId|string $nodeId,
        array         $selectFields = ['EventId', 'EventType', 'SourceName', 'Time', 'Message', 'Severity'],
        int           $clientHandle = 1,
    ): MonitoredItemResult
    {
        $nodeId = $this->resolveNodeIdParam($nodeId);
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
     * Delete monitored items from a subscription.
     *
     * @param int $subscriptionId The subscription owning the monitored items.
     * @param int[] $monitoredItemIds IDs of the monitored items to delete.
     * @return int[] OPC UA status codes for each deletion.
     *
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ConnectionException If the connection is lost during the request.
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ServiceException If the server returns an error response.
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
     * Delete a subscription and all its monitored items.
     *
     * @param int $subscriptionId The subscription to delete.
     * @return int The OPC UA status code for the deletion.
     *
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ConnectionException If the connection is lost during the request.
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ServiceException If the server returns an error response.
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
     * Send a publish request to receive pending notifications from subscriptions.
     *
     * @param array<array{subscriptionId: int, sequenceNumber: int}> $acknowledgements Previously received notifications to acknowledge.
     * @return PublishResult
     *
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ConnectionException If the connection is lost during the request.
     * @throws \Gianfriaur\OpcuaPhpClient\Exception\ServiceException If the server returns an error response.
     *
     * @see PublishResult
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
