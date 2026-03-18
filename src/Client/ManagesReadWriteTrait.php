<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Client;

use Gianfriaur\OpcuaPhpClient\Encoding\BinaryDecoder;
use Gianfriaur\OpcuaPhpClient\Exception\ConnectionException;
use Gianfriaur\OpcuaPhpClient\Types\BuiltinType;
use Gianfriaur\OpcuaPhpClient\Types\DataValue;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;
use Gianfriaur\OpcuaPhpClient\Types\Variant;

trait ManagesReadWriteTrait
{
    /**
     * @param NodeId $nodeId
     * @param int $attributeId
     * @return DataValue
     */
    public function read(NodeId $nodeId, int $attributeId = 13): DataValue
    {
        if ($this->readService === null || $this->authenticationToken === null) {
            throw new ConnectionException('Not connected');
        }

        $requestId = $this->nextRequestId();
        $request = $this->readService->encodeReadRequest($requestId, $nodeId, $this->authenticationToken, $attributeId);
        $this->transport->send($request);

        $response = $this->transport->receive();
        $responseBody = $this->unwrapResponse($response);
        $decoder = new BinaryDecoder($responseBody);

        return $this->readService->decodeReadResponse($decoder);
    }

    /**
     * @param array<array{nodeId: NodeId, attributeId?: int}> $items
     * @return DataValue[]
     */
    public function readMulti(array $items): array
    {
        if ($this->readService === null || $this->authenticationToken === null) {
            throw new ConnectionException('Not connected');
        }

        $requestId = $this->nextRequestId();
        $request = $this->readService->encodeReadMultiRequest($requestId, $items, $this->authenticationToken);
        $this->transport->send($request);

        $response = $this->transport->receive();
        $responseBody = $this->unwrapResponse($response);
        $decoder = new BinaryDecoder($responseBody);

        return $this->readService->decodeReadMultiResponse($decoder);
    }

    /**
     * @param NodeId $nodeId
     * @param mixed $value
     * @param BuiltinType $type
     * @return int
     */
    public function write(NodeId $nodeId, mixed $value, BuiltinType $type): int
    {
        if ($this->writeService === null || $this->authenticationToken === null) {
            throw new ConnectionException('Not connected');
        }

        $variant = new Variant($type, $value);
        $dataValue = new DataValue($variant);

        $requestId = $this->nextRequestId();
        $request = $this->writeService->encodeWriteRequest($requestId, $nodeId, $dataValue, $this->authenticationToken);
        $this->transport->send($request);

        $response = $this->transport->receive();
        $responseBody = $this->unwrapResponse($response);
        $decoder = new BinaryDecoder($responseBody);

        $results = $this->writeService->decodeWriteResponse($decoder);

        return $results[0] ?? 0;
    }

    /**
     * @param array<array{nodeId: NodeId, value: mixed, type: BuiltinType, attributeId?: int}> $items
     * @return int[]
     */
    public function writeMulti(array $items): array
    {
        if ($this->writeService === null || $this->authenticationToken === null) {
            throw new ConnectionException('Not connected');
        }

        $writeItems = [];
        foreach ($items as $item) {
            $variant = new Variant($item['type'], $item['value']);
            $writeItems[] = [
                'nodeId' => $item['nodeId'],
                'dataValue' => new DataValue($variant),
                'attributeId' => $item['attributeId'] ?? 13,
            ];
        }

        $requestId = $this->nextRequestId();
        $request = $this->writeService->encodeWriteMultiRequest($requestId, $writeItems, $this->authenticationToken);
        $this->transport->send($request);

        $response = $this->transport->receive();
        $responseBody = $this->unwrapResponse($response);
        $decoder = new BinaryDecoder($responseBody);

        return $this->writeService->decodeWriteResponse($decoder);
    }

    /**
     * @param NodeId $objectId
     * @param NodeId $methodId
     * @param Variant[] $inputArguments
     * @return array{statusCode: int, inputArgumentResults: int[], outputArguments: Variant[]}
     */
    public function call(NodeId $objectId, NodeId $methodId, array $inputArguments = []): array
    {
        if ($this->callService === null || $this->authenticationToken === null) {
            throw new ConnectionException('Not connected');
        }

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
        $decoder = new BinaryDecoder($responseBody);

        return $this->callService->decodeCallResponse($decoder);
    }
}
