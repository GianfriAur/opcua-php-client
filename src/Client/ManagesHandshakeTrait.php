<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Client;

use Gianfriaur\OpcuaPhpClient\Encoding\BinaryDecoder;
use Gianfriaur\OpcuaPhpClient\Exception\ProtocolException;
use Gianfriaur\OpcuaPhpClient\Exception\SecurityException;
use Gianfriaur\OpcuaPhpClient\Protocol\AcknowledgeMessage;
use Gianfriaur\OpcuaPhpClient\Protocol\GetEndpointsService;
use Gianfriaur\OpcuaPhpClient\Protocol\HelloMessage;
use Gianfriaur\OpcuaPhpClient\Protocol\MessageHeader;
use Gianfriaur\OpcuaPhpClient\Protocol\SecureChannelRequest;
use Gianfriaur\OpcuaPhpClient\Protocol\SecureChannelResponse;
use Gianfriaur\OpcuaPhpClient\Protocol\ServiceTypeId;
use Gianfriaur\OpcuaPhpClient\Protocol\SessionService;
use Gianfriaur\OpcuaPhpClient\Transport\TcpTransport;
use Gianfriaur\OpcuaPhpClient\Types\NodeId;

trait ManagesHandshakeTrait
{
    /**
     * @param string $endpointUrl
     */
    private function doHandshake(string $endpointUrl): void
    {
        $hello = new HelloMessage(endpointUrl: $endpointUrl);
        $this->transport->send($hello->encode());

        $response = $this->transport->receive();
        $decoder = new BinaryDecoder($response);
        $header = MessageHeader::decode($decoder);

        if ($header->getMessageType() === 'ERR') {
            $errorCode = $decoder->readUInt32();
            $errorMessage = $decoder->readString();
            throw new ProtocolException("Server error during handshake: [{$errorCode}] {$errorMessage}");
        }

        if ($header->getMessageType() !== 'ACK') {
            throw new ProtocolException("Expected ACK, got: {$header->getMessageType()}");
        }

        $ack = AcknowledgeMessage::decode($decoder);
        $this->transport->setReceiveBufferSize($ack->getReceiveBufferSize());
    }

    /**
     * @param string $host
     * @param int $port
     * @param string $endpointUrl
     */
    private function discoverServerCertificate(string $host, int $port, string $endpointUrl): void
    {
        $discoveryTransport = new TcpTransport();
        $discoveryTransport->connect($host, $port, $this->getTimeout());

        $session = $this->performDiscoveryHandshake($discoveryTransport, $endpointUrl);

        $getEndpointsService = new GetEndpointsService($session);
        $request = $getEndpointsService->encodeGetEndpointsRequest(1, $endpointUrl, NodeId::numeric(0, ServiceTypeId::NULL));
        $discoveryTransport->send($request);

        $response = $discoveryTransport->receive();
        $responseBody = substr($response, MessageHeader::HEADER_SIZE + 4);
        $decoder = new BinaryDecoder($responseBody);
        $endpoints = $getEndpointsService->decodeGetEndpointsResponse($decoder);

        $this->extractServerCertificateFromEndpoints($endpoints);

        $discoveryTransport->close();

        if ($this->serverCertDer === null) {
            throw new SecurityException('Could not obtain server certificate from GetEndpoints');
        }
    }

    /**
     * @param TcpTransport $transport
     * @param string $endpointUrl
     * @return SessionService
     */
    private function performDiscoveryHandshake(TcpTransport $transport, string $endpointUrl): SessionService
    {
        $helloMessage = new HelloMessage(endpointUrl: $endpointUrl);
        $transport->send($helloMessage->encode());
        $helloResponse = $transport->receive();
        $helloDecoder = new BinaryDecoder($helloResponse);
        $helloHeader = MessageHeader::decode($helloDecoder);
        if ($helloHeader->getMessageType() !== 'ACK') {
            throw new ProtocolException("Discovery: Expected ACK, got: {$helloHeader->getMessageType()}");
        }
        AcknowledgeMessage::decode($helloDecoder);

        $opnRequest = new SecureChannelRequest();
        $transport->send($opnRequest->encode());
        $opnResponse = $transport->receive();
        $opnDecoder = new BinaryDecoder($opnResponse);
        $opnHeader = MessageHeader::decode($opnDecoder);
        if ($opnHeader->getMessageType() !== 'OPN') {
            throw new ProtocolException("Discovery: Expected OPN, got: {$opnHeader->getMessageType()}");
        }
        $opnDecoder->readUInt32();
        $scResponse = SecureChannelResponse::decode($opnDecoder);

        return new SessionService($scResponse->getSecureChannelId(), $scResponse->getTokenId());
    }

    /**
     * @param \Gianfriaur\OpcuaPhpClient\Types\EndpointDescription[] $endpoints
     */
    private function extractServerCertificateFromEndpoints(array $endpoints): void
    {
        foreach ($endpoints as $ep) {
            if ($ep->getSecurityPolicyUri() === $this->securityPolicy->value
                && $ep->getSecurityMode() === $this->securityMode->value
                && $ep->getServerCertificate() !== null
            ) {
                $this->serverCertDer = $ep->getServerCertificate();
                $this->extractTokenPolicies($ep);

                break;
            }
        }

        if ($this->serverCertDer === null) {
            foreach ($endpoints as $ep) {
                if ($ep->getServerCertificate() !== null) {
                    $this->serverCertDer = $ep->getServerCertificate();

                    break;
                }
            }
        }
    }

    /**
     * @param \Gianfriaur\OpcuaPhpClient\Types\EndpointDescription $endpoint
     */
    private function extractTokenPolicies(\Gianfriaur\OpcuaPhpClient\Types\EndpointDescription $endpoint): void
    {
        foreach ($endpoint->getUserIdentityTokens() as $tokenPolicy) {
            match ($tokenPolicy->getTokenType()) {
                1 => $this->usernamePolicyId = $tokenPolicy->getPolicyId(),
                2 => $this->certificatePolicyId = $tokenPolicy->getPolicyId(),
                0 => $this->anonymousPolicyId = $tokenPolicy->getPolicyId(),
                default => null,
            };
        }
    }
}
