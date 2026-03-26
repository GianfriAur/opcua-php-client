<?php

declare(strict_types=1);

use PhpOpcua\Client\Cli\Commands\TrustCommand;
use PhpOpcua\Client\Cli\Commands\TrustListCommand;
use PhpOpcua\Client\Cli\Commands\TrustRemoveCommand;
use PhpOpcua\Client\Cli\Output\ConsoleOutput;
use PhpOpcua\Client\Security\CertificateManager;
use PhpOpcua\Client\Testing\MockClient;
use PhpOpcua\Client\TrustStore\FileTrustStore;
use PhpOpcua\Client\Types\EndpointDescription;
use PhpOpcua\Client\Types\UserTokenPolicy;

function trustTestOutputStream(): array
{
    $stdout = fopen(tempnam(sys_get_temp_dir(), 'opcua-test-'), 'w+');
    $stderr = fopen(tempnam(sys_get_temp_dir(), 'opcua-test-'), 'w+');

    return [$stdout, $stderr];
}

function trustTestStreamContent($stream): string
{
    rewind($stream);

    return stream_get_contents($stream);
}

function trustTestStore(): FileTrustStore
{
    return new FileTrustStore(sys_get_temp_dir() . '/opcua-cli-trust-test-' . uniqid());
}

function trustTestCleanup(FileTrustStore $store): void
{
    foreach ([$store->getTrustedDir(), $store->getRejectedDir()] as $dir) {
        foreach (glob($dir . '/*.der') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($dir);
    }
    @rmdir(dirname($store->getTrustedDir()));
}

describe('TrustCommand', function () {

    it('returns name and description', function () {
        $cmd = new TrustCommand();
        expect($cmd->getName())->toBe('trust');
        expect($cmd->getDescription())->toBeString();
        expect($cmd->requiresConnection())->toBeTrue();
    });

    it('returns 1 when no arguments', function () {
        $cmd = new TrustCommand();
        $client = MockClient::create();
        [$stdout, $stderr] = trustTestOutputStream();
        $output = new ConsoleOutput($stdout, $stderr);
        expect($cmd->execute($client, [], [], $output))->toBe(1);
    });

    it('returns 1 when no trust store configured', function () {
        $cmd = new TrustCommand();
        $client = MockClient::create();
        [$stdout, $stderr] = trustTestOutputStream();
        $output = new ConsoleOutput($stdout, $stderr);
        expect($cmd->execute($client, ['opc.tcp://localhost'], [], $output))->toBe(1);
    });

    it('returns 1 when no cert found in endpoints', function () {
        $cmd = new TrustCommand();
        $store = trustTestStore();
        $client = MockClient::create()->setTrustStore($store);
        [$stdout, $stderr] = trustTestOutputStream();
        $output = new ConsoleOutput($stdout, $stderr);
        expect($cmd->execute($client, ['opc.tcp://localhost'], [], $output))->toBe(1);
        trustTestCleanup($store);
    });

    it('trusts cert from endpoint and shows details', function () {
        $cmd = new TrustCommand();
        $store = trustTestStore();
        $certDer = (new CertificateManager())->generateSelfSignedCertificate()['certDer'];
        $client = MockClient::create()
            ->setTrustStore($store)
            ->onGetEndpoints(fn () => [
                new EndpointDescription(
                    'opc.tcp://localhost:4840',
                    $certDer,
                    1,
                    'http://opcfoundation.org/UA/SecurityPolicy#None',
                    [new UserTokenPolicy('anon', 0, null, null, null)],
                    '',
                    0,
                ),
            ]);

        [$stdout, $stderr] = trustTestOutputStream();
        $output = new ConsoleOutput($stdout, $stderr);
        $code = $cmd->execute($client, ['opc.tcp://localhost:4840'], [], $output);

        expect($code)->toBe(0);
        $content = trustTestStreamContent($stdout);
        expect($content)->toContain('Trusted');
        expect($content)->toContain('Fingerprint');

        expect($store->isTrusted($certDer))->toBeTrue();
        trustTestCleanup($store);
    });

});

describe('TrustListCommand', function () {

    it('returns name and description', function () {
        $cmd = new TrustListCommand();
        expect($cmd->getName())->toBe('trust:list');
        expect($cmd->requiresConnection())->toBeFalse();
    });

    it('returns 1 when no trust store', function () {
        $cmd = new TrustListCommand();
        $client = MockClient::create();
        [$stdout, $stderr] = trustTestOutputStream();
        $output = new ConsoleOutput($stdout, $stderr);
        expect($cmd->execute($client, [], [], $output))->toBe(1);
    });

    it('shows empty list message', function () {
        $cmd = new TrustListCommand();
        $store = trustTestStore();
        $client = MockClient::create()->setTrustStore($store);
        [$stdout, $stderr] = trustTestOutputStream();
        $output = new ConsoleOutput($stdout, $stderr);
        $code = $cmd->execute($client, [], [], $output);
        expect($code)->toBe(0);
        expect(trustTestStreamContent($stdout))->toContain('No trusted certificates');
        trustTestCleanup($store);
    });

    it('lists trusted certs', function () {
        $cmd = new TrustListCommand();
        $store = trustTestStore();
        $cert = (new CertificateManager())->generateSelfSignedCertificate()['certDer'];
        $store->trust($cert);
        $client = MockClient::create()->setTrustStore($store);
        [$stdout, $stderr] = trustTestOutputStream();
        $output = new ConsoleOutput($stdout, $stderr);
        $code = $cmd->execute($client, [], [], $output);
        expect($code)->toBe(0);
        expect(trustTestStreamContent($stdout))->toContain('Fingerprint');
        trustTestCleanup($store);
    });

});

describe('TrustRemoveCommand', function () {

    it('returns name and description', function () {
        $cmd = new TrustRemoveCommand();
        expect($cmd->getName())->toBe('trust:remove');
        expect($cmd->requiresConnection())->toBeFalse();
    });

    it('returns 1 when no arguments', function () {
        $cmd = new TrustRemoveCommand();
        $client = MockClient::create();
        [$stdout, $stderr] = trustTestOutputStream();
        $output = new ConsoleOutput($stdout, $stderr);
        expect($cmd->execute($client, [], [], $output))->toBe(1);
    });

    it('returns 1 when no trust store', function () {
        $cmd = new TrustRemoveCommand();
        $client = MockClient::create();
        [$stdout, $stderr] = trustTestOutputStream();
        $output = new ConsoleOutput($stdout, $stderr);
        expect($cmd->execute($client, ['aa:bb:cc'], [], $output))->toBe(1);
    });

    it('removes a trusted cert by fingerprint', function () {
        $cmd = new TrustRemoveCommand();
        $store = trustTestStore();
        $cert = (new CertificateManager())->generateSelfSignedCertificate()['certDer'];
        $store->trust($cert);
        $fingerprint = implode(':', str_split(sha1($cert), 2));
        $client = MockClient::create()->setTrustStore($store);
        [$stdout, $stderr] = trustTestOutputStream();
        $output = new ConsoleOutput($stdout, $stderr);
        $code = $cmd->execute($client, [$fingerprint], [], $output);
        expect($code)->toBe(0);
        expect($store->isTrusted($cert))->toBeFalse();
        expect(trustTestStreamContent($stdout))->toContain('Removed');
        trustTestCleanup($store);
    });

});
