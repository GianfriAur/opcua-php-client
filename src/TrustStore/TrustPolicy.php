<?php

declare(strict_types=1);

namespace PhpOpcua\Client\TrustStore;

/**
 * Defines the level of certificate validation performed by the trust store.
 */
enum TrustPolicy: string
{
    case Fingerprint = 'fingerprint';

    case FingerprintAndExpiry = 'fingerprint+expiry';

    case Full = 'full';
}
