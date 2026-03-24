<?php

declare(strict_types=1);

use Gianfriaur\OpcuaPhpClient\Security\CertificateManager;
use Gianfriaur\OpcuaPhpClient\TrustStore\FileTrustStore;
use Gianfriaur\OpcuaPhpClient\TrustStore\TrustPolicy;
use Gianfriaur\OpcuaPhpClient\TrustStore\TrustResult;

function generateTestCert(): string
{
    $cm = new CertificateManager();
    $generated = $cm->generateSelfSignedCertificate();

    return $generated['certDer'];
}

function generateExpiredTestCert(): string
{
    $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
    $csr = openssl_csr_new(['CN' => 'Expired Test'], $key);
    $cert = openssl_csr_sign($csr, null, $key, 0);
    openssl_x509_export($cert, $pem);
    $der = base64_decode(
        str_replace(['-----BEGIN CERTIFICATE-----', '-----END CERTIFICATE-----', "\n", "\r"], '', $pem),
    );

    return $der;
}

function createTempTrustStore(): FileTrustStore
{
    $dir = sys_get_temp_dir() . '/opcua-trust-test-' . uniqid();

    return new FileTrustStore($dir);
}

function cleanupTrustStore(FileTrustStore $store): void
{
    $dirs = [$store->getTrustedDir(), $store->getRejectedDir()];
    foreach ($dirs as $dir) {
        $files = glob($dir . '/*.der') ?: [];
        foreach ($files as $file) {
            @unlink($file);
        }
        @rmdir($dir);
    }
    @rmdir(dirname($store->getTrustedDir()));
}

describe('FileTrustStore', function () {

    it('creates trusted and rejected directories on construction', function () {
        $store = createTempTrustStore();
        expect(is_dir($store->getTrustedDir()))->toBeTrue();
        expect(is_dir($store->getRejectedDir()))->toBeTrue();
        cleanupTrustStore($store);
    });

    it('trusts a certificate and checks isTrusted', function () {
        $store = createTempTrustStore();
        $cert = generateTestCert();

        expect($store->isTrusted($cert))->toBeFalse();
        $store->trust($cert);
        expect($store->isTrusted($cert))->toBeTrue();

        cleanupTrustStore($store);
    });

    it('untrusts a certificate by fingerprint', function () {
        $store = createTempTrustStore();
        $cert = generateTestCert();
        $store->trust($cert);

        $fingerprint = implode(':', str_split(sha1($cert), 2));
        $store->untrust($fingerprint);

        expect($store->isTrusted($cert))->toBeFalse();
        cleanupTrustStore($store);
    });

    it('untrusts with plain hex fingerprint', function () {
        $store = createTempTrustStore();
        $cert = generateTestCert();
        $store->trust($cert);

        $fingerprint = sha1($cert);
        $store->untrust($fingerprint);

        expect($store->isTrusted($cert))->toBeFalse();
        cleanupTrustStore($store);
    });

    it('untrust on non-existent fingerprint does nothing', function () {
        $store = createTempTrustStore();
        $store->untrust('aa:bb:cc:dd:ee:ff:00:11:22:33:44:55:66:77:88:99:aa:bb:cc:dd');
        expect(true)->toBeTrue();
        cleanupTrustStore($store);
    });

    it('rejects a certificate', function () {
        $store = createTempTrustStore();
        $cert = generateTestCert();
        $store->reject($cert);

        $fingerprint = sha1($cert);
        $rejectedPath = $store->getRejectedDir() . '/' . $fingerprint . '.der';
        expect(file_exists($rejectedPath))->toBeTrue();

        cleanupTrustStore($store);
    });

    it('trust removes from rejected if present', function () {
        $store = createTempTrustStore();
        $cert = generateTestCert();
        $store->reject($cert);

        $fingerprint = sha1($cert);
        $rejectedPath = $store->getRejectedDir() . '/' . $fingerprint . '.der';
        expect(file_exists($rejectedPath))->toBeTrue();

        $store->trust($cert);
        expect(file_exists($rejectedPath))->toBeFalse();
        expect($store->isTrusted($cert))->toBeTrue();

        cleanupTrustStore($store);
    });

    it('lists trusted certificates with metadata', function () {
        $store = createTempTrustStore();
        $cert1 = generateTestCert();
        $cert2 = generateTestCert();
        $store->trust($cert1);
        $store->trust($cert2);

        $certs = $store->getTrustedCertificates();
        expect($certs)->toHaveCount(2);
        expect($certs[0])->toHaveKeys(['fingerprint', 'subject', 'notAfter', 'path']);
        expect($certs[0]['fingerprint'])->toBeString();
        expect($certs[0]['subject'])->not->toBeNull();

        cleanupTrustStore($store);
    });

    it('returns empty array when no trusted certificates', function () {
        $store = createTempTrustStore();
        expect($store->getTrustedCertificates())->toBe([]);
        cleanupTrustStore($store);
    });

});

describe('FileTrustStore validation', function () {

    it('validates trusted cert with Fingerprint policy', function () {
        $store = createTempTrustStore();
        $cert = generateTestCert();
        $store->trust($cert);

        $result = $store->validate($cert, TrustPolicy::Fingerprint);
        expect($result)->toBeInstanceOf(TrustResult::class);
        expect($result->trusted)->toBeTrue();
        expect($result->fingerprint)->toBeString();
        expect($result->reason)->toBeNull();

        cleanupTrustStore($store);
    });

    it('rejects untrusted cert with Fingerprint policy', function () {
        $store = createTempTrustStore();
        $cert = generateTestCert();

        $result = $store->validate($cert, TrustPolicy::Fingerprint);
        expect($result->trusted)->toBeFalse();
        expect($result->reason)->toContain('not found');

        cleanupTrustStore($store);
    });

    it('validates trusted non-expired cert with FingerprintAndExpiry policy', function () {
        $store = createTempTrustStore();
        $cert = generateTestCert();
        $store->trust($cert);

        $result = $store->validate($cert, TrustPolicy::FingerprintAndExpiry);
        expect($result->trusted)->toBeTrue();
        expect($result->notAfter)->toBeInstanceOf(DateTimeImmutable::class);

        cleanupTrustStore($store);
    });

    it('validates with Full policy without CA cert', function () {
        $store = createTempTrustStore();
        $cert = generateTestCert();
        $store->trust($cert);

        $result = $store->validate($cert, TrustPolicy::Full);
        expect($result->trusted)->toBeTrue();

        cleanupTrustStore($store);
    });

    it('rejects expired cert with FingerprintAndExpiry policy', function () {
        $store = createTempTrustStore();
        $cert = generateTestCert();
        $store->trust($cert);

        $fingerprint = sha1($cert);
        $trustedPath = $store->getTrustedDir() . '/' . $fingerprint . '.der';

        $expiredCert = generateExpiredTestCert();
        $store->trust($expiredCert);

        $result = $store->validate($expiredCert, TrustPolicy::FingerprintAndExpiry);
        expect($result->trusted)->toBeFalse();
        expect($result->reason)->toContain('expired');

        cleanupTrustStore($store);
    });

    it('validates with Full policy and CA cert', function () {
        $store = createTempTrustStore();
        $cert = generateTestCert();
        $store->trust($cert);

        $pem = "-----BEGIN CERTIFICATE-----\n" . chunk_split(base64_encode($cert), 64) . "-----END CERTIFICATE-----\n";

        $result = $store->validate($cert, TrustPolicy::Full, $pem);
        expect($result)->toBeInstanceOf(TrustResult::class);

        cleanupTrustStore($store);
    });

    it('includes subject and dates in TrustResult', function () {
        $store = createTempTrustStore();
        $cert = generateTestCert();
        $store->trust($cert);

        $result = $store->validate($cert, TrustPolicy::Fingerprint);
        expect($result->subject)->not->toBeNull();
        expect($result->notBefore)->toBeInstanceOf(DateTimeImmutable::class);
        expect($result->notAfter)->toBeInstanceOf(DateTimeImmutable::class);

        cleanupTrustStore($store);
    });

    it('verifyCaChain returns false for invalid cert', function () {
        $store = createTempTrustStore();
        $method = new ReflectionMethod($store, 'verifyCaChain');
        $result = $method->invoke($store, 'invalid-der-data', 'invalid-pem-data');
        expect($result)->toBeFalse();
        cleanupTrustStore($store);
    });

    it('validates with Full policy and invalid CA rejects', function () {
        $store = createTempTrustStore();
        $cert = generateTestCert();
        $store->trust($cert);

        $otherCert = generateTestCert();
        $otherPem = "-----BEGIN CERTIFICATE-----\n" . chunk_split(base64_encode($otherCert), 64) . "-----END CERTIFICATE-----\n";

        $result = $store->validate($cert, TrustPolicy::Full, $otherPem);
        expect($result->trusted)->toBeFalse();
        expect($result->reason)->toContain('chain verification failed');

        cleanupTrustStore($store);
    });

    it('parseCertificateInfo returns nulls for invalid cert', function () {
        $store = createTempTrustStore();
        $method = new ReflectionMethod($store, 'parseCertificateInfo');
        $result = $method->invoke($store, 'not-a-valid-cert');
        expect($result['subject'])->toBeNull();
        expect($result['notBefore'])->toBeNull();
        expect($result['notAfter'])->toBeNull();
        cleanupTrustStore($store);
    });

    it('uses default base path when none provided', function () {
        $store = new FileTrustStore();
        $home = $_SERVER['HOME'] ?? getenv('HOME') ?: sys_get_temp_dir();
        expect($store->getTrustedDir())->toContain('.opcua/trusted');
        expect(str_starts_with($store->getTrustedDir(), $home))->toBeTrue();
    });

});

describe('TrustPolicy enum', function () {

    it('has three cases', function () {
        expect(TrustPolicy::cases())->toHaveCount(3);
    });

    it('has correct values', function () {
        expect(TrustPolicy::Fingerprint->value)->toBe('fingerprint');
        expect(TrustPolicy::FingerprintAndExpiry->value)->toBe('fingerprint+expiry');
        expect(TrustPolicy::Full->value)->toBe('full');
    });

    it('creates from string', function () {
        expect(TrustPolicy::from('fingerprint'))->toBe(TrustPolicy::Fingerprint);
        expect(TrustPolicy::from('full'))->toBe(TrustPolicy::Full);
    });

});

describe('TrustResult', function () {

    it('creates trusted result', function () {
        $result = new TrustResult(true, 'aa:bb:cc', null, 'CN=Server');
        expect($result->trusted)->toBeTrue();
        expect($result->fingerprint)->toBe('aa:bb:cc');
        expect($result->reason)->toBeNull();
        expect($result->subject)->toBe('CN=Server');
    });

    it('creates rejected result with reason', function () {
        $result = new TrustResult(false, 'aa:bb:cc', 'Certificate expired');
        expect($result->trusted)->toBeFalse();
        expect($result->reason)->toBe('Certificate expired');
    });

});
