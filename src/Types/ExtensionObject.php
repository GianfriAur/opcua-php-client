<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Types;

/**
 * Represents an OPC UA ExtensionObject containing a typed binary or XML payload.
 *
 * When a codec is registered for the type, the decoded value is available via {@see $value}
 * and {@see isDecoded()} returns true. When no codec is registered, the raw body is available
 * via {@see $body} and {@see isRaw()} returns true.
 *
 * @see \PhpOpcua\Client\Encoding\BinaryDecoder::readExtensionObject()
 * @see \PhpOpcua\Client\Encoding\ExtensionObjectCodec
 */
readonly class ExtensionObject
{
    /**
     * @param NodeId $typeId The encoding NodeId identifying the ExtensionObject type.
     * @param int $encoding The encoding format (0x01 = binary, 0x02 = XML, 0x00 = no body).
     * @param ?string $body The raw body bytes (binary or XML string). Null when decoded via codec.
     * @param mixed $value The decoded value from a registered codec. Null when raw (no codec).
     */
    public function __construct(
        public NodeId $typeId,
        public int $encoding,
        public ?string $body = null,
        public mixed $value = null,
    ) {
    }

    /**
     * Whether this ExtensionObject has been decoded by a registered codec.
     *
     * @return bool
     */
    public function isDecoded(): bool
    {
        return $this->value !== null;
    }

    /**
     * Whether this ExtensionObject contains raw (undecoded) data.
     *
     * @return bool
     */
    public function isRaw(): bool
    {
        return $this->value === null;
    }
}
