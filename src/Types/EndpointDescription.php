<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Types;

class EndpointDescription
{
    /**
     * @param string $endpointUrl
     * @param ?string $serverCertificate
     * @param int $securityMode
     * @param string $securityPolicyUri
     * @param UserTokenPolicy[] $userIdentityTokens
     * @param string $transportProfileUri
     * @param int $securityLevel
     */
    public function __construct(
        public readonly string  $endpointUrl,
        public readonly ?string $serverCertificate,
        public readonly int     $securityMode,
        public readonly string  $securityPolicyUri,
        public readonly array   $userIdentityTokens,
        public readonly string  $transportProfileUri,
        public readonly int     $securityLevel,
    )
    {
    }

    /** @deprecated Access the public property directly instead. Use ->endpointUrl instead. */
    public function getEndpointUrl(): string
    {
        return $this->endpointUrl;
    }

    /** @deprecated Access the public property directly instead. Use ->serverCertificate instead. */
    public function getServerCertificate(): ?string
    {
        return $this->serverCertificate;
    }

    /** @deprecated Access the public property directly instead. Use ->securityMode instead. */
    public function getSecurityMode(): int
    {
        return $this->securityMode;
    }

    /** @deprecated Access the public property directly instead. Use ->securityPolicyUri instead. */
    public function getSecurityPolicyUri(): string
    {
        return $this->securityPolicyUri;
    }

    /**
     * @deprecated Access the public property directly instead. Use ->userIdentityTokens instead.
     * @return UserTokenPolicy[]
     */
    public function getUserIdentityTokens(): array
    {
        return $this->userIdentityTokens;
    }

    /** @deprecated Access the public property directly instead. Use ->transportProfileUri instead. */
    public function getTransportProfileUri(): string
    {
        return $this->transportProfileUri;
    }

    /** @deprecated Access the public property directly instead. Use ->securityLevel instead. */
    public function getSecurityLevel(): int
    {
        return $this->securityLevel;
    }
}
