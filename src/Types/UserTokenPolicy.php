<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

/**
 * Represents an OPC UA UserTokenPolicy describing an accepted user identity token type.
 */
readonly class UserTokenPolicy
{
    /**
     * @param ?string $policyId
     * @param int $tokenType
     * @param ?string $issuedTokenType
     * @param ?string $issuerEndpointUrl
     * @param ?string $securityPolicyUri
     */
    public function __construct(
        public ?string $policyId,
        public int     $tokenType,
        public ?string $issuedTokenType,
        public ?string $issuerEndpointUrl,
        public ?string $securityPolicyUri,
    )
    {
    }

    /**
     * @deprecated Access the public property directly instead. Use ->policyId instead.
     * @return ?string
     * @see UserTokenPolicy::$policyId
     */
    public function getPolicyId(): ?string
    {
        return $this->policyId;
    }

    /**
     * @deprecated Access the public property directly instead. Use ->tokenType instead.
     * @return int
     * @see UserTokenPolicy::$tokenType
     */
    public function getTokenType(): int
    {
        return $this->tokenType;
    }

    /**
     * @deprecated Access the public property directly instead. Use ->issuedTokenType instead.
     * @return ?string
     * @see UserTokenPolicy::$issuedTokenType
     */
    public function getIssuedTokenType(): ?string
    {
        return $this->issuedTokenType;
    }

    /**
     * @deprecated Access the public property directly instead. Use ->issuerEndpointUrl instead.
     * @return ?string
     * @see UserTokenPolicy::$issuerEndpointUrl
     */
    public function getIssuerEndpointUrl(): ?string
    {
        return $this->issuerEndpointUrl;
    }

    /**
     * @deprecated Access the public property directly instead. Use ->securityPolicyUri instead.
     * @return ?string
     * @see UserTokenPolicy::$securityPolicyUri
     */
    public function getSecurityPolicyUri(): ?string
    {
        return $this->securityPolicyUri;
    }
}
