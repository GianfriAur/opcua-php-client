<?php

declare(strict_types=1);

use PhpOpcua\Client\Encoding\BinaryDecoder;
use PhpOpcua\Client\Encoding\BinaryEncoder;
use PhpOpcua\Client\Encoding\ExtensionObjectCodec;
use PhpOpcua\Client\Repository\ExtensionObjectRepository;
use PhpOpcua\Client\Types\ExtensionObject;
use PhpOpcua\Client\Types\NodeId;

class PartialConsumeCodec implements ExtensionObjectCodec
{
    public function decode(BinaryDecoder $decoder): array
    {
        return ['x' => $decoder->readDouble()];
    }

    public function encode(BinaryEncoder $encoder, mixed $value): void
    {
        $encoder->writeDouble($value['x']);
    }
}

describe('ExtensionObject codec partial body consumption', function () {

    it('skips unconsumed bytes when codec reads less than bodyLength', function () {
        $repo = new ExtensionObjectRepository();
        $typeId = NodeId::numeric(2, 9999);
        $repo->register($typeId, PartialConsumeCodec::class);

        $encoder = new BinaryEncoder();
        $encoder->writeNodeId($typeId);
        $encoder->writeByte(0x01);

        $bodyEncoder = new BinaryEncoder();
        $bodyEncoder->writeDouble(1.5);
        $bodyEncoder->writeDouble(2.5);
        $bodyEncoder->writeDouble(3.5);
        $body = $bodyEncoder->getBuffer();

        $encoder->writeInt32(strlen($body));
        $encoder->writeRawBytes($body);
        $encoder->writeInt32(12345);

        $decoder = new BinaryDecoder($encoder->getBuffer(), $repo);
        $result = $decoder->readExtensionObject();

        expect($result)->toBeInstanceOf(ExtensionObject::class);
        expect($result->isDecoded())->toBeTrue();
        expect($result->value)->toBe(['x' => 1.5]);

        $sentinel = $decoder->readInt32();
        expect($sentinel)->toBe(12345);
    });
});
