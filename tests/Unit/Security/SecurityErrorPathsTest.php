<?php

declare(strict_types=1);

use PhpOpcua\Client\Exception\SecurityException;
use PhpOpcua\Client\Security\CertificateManager;
use PhpOpcua\Client\Security\MessageSecurity;
use PhpOpcua\Client\Security\SecurityPolicy;

function generateErrorTestCert(): array
{
    $privKey = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    $csr = openssl_csr_new(['CN' => 'test'], $privKey);
    $cert = openssl_csr_sign($csr, null, $privKey, 365);
    openssl_x509_export($cert, $pem);

    $cm = new CertificateManager();
    $tmp = tempnam(sys_get_temp_dir(), 'opcua_');
    file_put_contents($tmp, $pem);
    $der = $cm->loadCertificatePem($tmp);
    unlink($tmp);

    return [$der, $privKey];
}

describe('MessageSecurity symmetric error paths', function () {

    it('symmetricEncrypt throws on non-block-aligned data', function () {
        $ms = new MessageSecurity();
        $key = random_bytes(32);
        $iv = random_bytes(16);
        $data = random_bytes(7);

        expect(fn () => $ms->symmetricEncrypt($data, $key, $iv, SecurityPolicy::Basic256Sha256))
            ->toThrow(SecurityException::class, 'Symmetric encryption failed');
    });

    it('symmetricDecrypt throws on non-block-aligned data', function () {
        $ms = new MessageSecurity();
        $key = random_bytes(32);
        $iv = random_bytes(16);
        $data = random_bytes(7);

        expect(fn () => $ms->symmetricDecrypt($data, $key, $iv, SecurityPolicy::Basic256Sha256))
            ->toThrow(SecurityException::class, 'Symmetric decryption failed');
    });
});

describe('MessageSecurity asymmetric error paths', function () {

    it('asymmetricDecrypt throws on corrupted ciphertext', function () {
        [, $privKey] = generateErrorTestCert();
        $ms = new MessageSecurity();

        $garbage = random_bytes(256);

        expect(fn () => $ms->asymmetricDecrypt($garbage, $privKey, SecurityPolicy::Basic256Sha256))
            ->toThrow(SecurityException::class, 'Asymmetric decryption failed');
    });

    it('asymmetricEncrypt throws on corrupted certificate', function () {
        $ms = new MessageSecurity();
        $fakeDer = random_bytes(100);

        expect(fn () => $ms->asymmetricEncrypt('data', $fakeDer, SecurityPolicy::Basic256Sha256))
            ->toThrow(SecurityException::class);
    });

    it('asymmetricVerify throws on corrupted certificate', function () {
        $ms = new MessageSecurity();
        $fakeDer = random_bytes(100);

        expect(fn () => $ms->asymmetricVerify('data', 'sig', $fakeDer, SecurityPolicy::Basic256Sha256))
            ->toThrow(SecurityException::class);
    });
});

describe('CertificateManager error paths', function () {

    it('getPublicKeyLength throws on invalid DER', function () {
        $cm = new CertificateManager();

        expect(fn () => $cm->getPublicKeyLength('not-a-certificate'))
            ->toThrow(SecurityException::class, 'Failed to read certificate');
    });

    it('getPublicKeyFromCert throws on invalid DER', function () {
        $cm = new CertificateManager();

        expect(fn () => $cm->getPublicKeyFromCert('not-a-certificate'))
            ->toThrow(SecurityException::class, 'Failed to read certificate');
    });

    it('pemToDer throws on invalid base64', function () {
        $cm = new CertificateManager();
        $badPem = "-----BEGIN CERTIFICATE-----\n!!invalid!!\n-----END CERTIFICATE-----\n";

        $tmp = tempnam(sys_get_temp_dir(), 'opcua_bad_');
        file_put_contents($tmp, $badPem);

        expect(fn () => $cm->loadCertificatePem($tmp))
            ->toThrow(SecurityException::class, 'Failed to decode PEM');

        unlink($tmp);
    });
});
