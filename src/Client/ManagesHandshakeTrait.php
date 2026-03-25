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

/**
 * Provides OPC UA handshake and server certificate discovery for the connected client.
 */
trait ManagesHandshakeTrait
{
    /**
     * Perform the HEL/ACK handshake with the server.
     *
     * @param string $endpointUrl The OPC UA endpoint URL.
     * @return void
     *
     * @throws ProtocolException If the server responds with an error or unexpected message type.
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
     * Discover the server certificate by connecting with no security and querying endpoints.
     *
     * @param string $host The server hostname.
     * @param int $port The server port.
     * @param string $endpointUrl The OPC UA endpoint URL.
     * @return void
     *
     * @throws SecurityException If the server certificate cannot be obtained.
     * @throws ProtocolException If the discovery handshake fails.
     */
    private function discoverServerCertificate(string $host, int $port, string $endpointUrl): void
    {
        $discoveryTransport = new TcpTransport();
        $discoveryTransport->connect($host, $port, $this->timeout);

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
     * Perform a discovery handshake on a temporary transport to obtain a SessionService.
     *
     * @param TcpTransport $transport The temporary transport.
     * @param string $endpointUrl The OPC UA endpoint URL.
     * @return SessionService
     *
     * @throws ProtocolException If the handshake fails.
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
     * Extract the server certificate from discovered endpoints matching the configured security.
     *
     * @param \Gianfriaur\OpcuaPhpClient\Types\EndpointDescription[] $endpoints
     * @return void
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
     * Extract user identity token policy IDs from an endpoint description.
     *
     * @param \Gianfriaur\OpcuaPhpClient\Types\EndpointDescription $endpoint
     * @return void
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
