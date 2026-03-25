<?php

declare(strict_types=1);

require_once __DIR__ . '/ClientTraitsCoverageTest.php';

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class TestLogger implements LoggerInterface
{
    public array $logs = [];

    public function emergency(Stringable|string $message, array $context = []): void
    {
        $this->logs[] = ['emergency', $message, $context];
    }

    public function alert(Stringable|string $message, array $context = []): void
    {
        $this->logs[] = ['alert', $message, $context];
    }

    public function critical(Stringable|string $message, array $context = []): void
    {
        $this->logs[] = ['critical', $message, $context];
    }

    public function error(Stringable|string $message, array $context = []): void
    {
        $this->logs[] = ['error', $message, $context];
    }

    public function warning(Stringable|string $message, array $context = []): void
    {
        $this->logs[] = ['warning', $message, $context];
    }

    public function notice(Stringable|string $message, array $context = []): void
    {
        $this->logs[] = ['notice', $message, $context];
    }

    public function info(Stringable|string $message, array $context = []): void
    {
        $this->logs[] = ['info', $message, $context];
    }

    public function debug(Stringable|string $message, array $context = []): void
    {
        $this->logs[] = ['debug', $message, $context];
    }

    public function log($level, Stringable|string $message, array $context = []): void
    {
        $this->logs[] = [$level, $message, $context];
    }
}

describe('Client PSR-3 Logger', function () {

    it('uses NullLogger by default', function () {
        $client = createClientWithoutConnect();
        expect($client->getLogger())->toBeInstanceOf(NullLogger::class);
    });

    it('accepts logger in builder constructor', function () {
        $logger = new TestLogger();
        $builder = new Gianfriaur\OpcuaPhpClient\ClientBuilder(logger: $logger);

        expect($builder->getLogger())->toBe($logger);
    });

    it('setLogger is fluent on builder', function () {
        $builder = new Gianfriaur\OpcuaPhpClient\ClientBuilder();
        $logger = new TestLogger();

        $result = $builder->setLogger($logger);

        expect($result)->toBe($builder);
        expect($builder->getLogger())->toBe($logger);
    });

    it('logs connection events via MockTransport', function () {
        $logger = new TestLogger();
        $mock = new MockTransport();

        $mock->addResponse(readResponseMsg(42));

        $client = setupConnectedClient($mock);
        setClientProperty($client, 'logger', $logger);

        $client->read('i=2259');

        $client->disconnect();

        $levels = array_column($logger->logs, 0);
        expect($levels)->toContain('info');
    });

    it('logs disconnect', function () {
        $logger = new TestLogger();
        $client = createClientWithoutConnect();
        setClientProperty($client, 'logger', $logger);

        $client->disconnect();

        $messages = array_column($logger->logs, 1);
        expect($messages)->toContain('Disconnecting');
    });

    it('logs error on retry exhaustion', function () {
        $logger = new TestLogger();
        $mock = new MockTransport();

        $client = setupConnectedClient($mock);
        setClientProperty($client, 'logger', $logger);
        setClientProperty($client, 'autoRetry', 0);

        try {
            $client->read('i=2259');
        } catch (Gianfriaur\OpcuaPhpClient\Exception\ConnectionException) {
        }

        $levels = array_column($logger->logs, 0);
        expect($levels)->toContain('error');
    });

    it('logs batch split on readMulti', function () {
        $logger = new TestLogger();
        $mock = new MockTransport();

        $mock->addResponse(buildMsgResponse(634, function (Gianfriaur\OpcuaPhpClient\Encoding\BinaryEncoder $e) {
            $e->writeInt32(1);
            $e->writeByte(0x01);
            $e->writeByte(Gianfriaur\OpcuaPhpClient\Types\BuiltinType::Int32->value);
            $e->writeInt32(1);
            $e->writeInt32(0);
        }));
        $mock->addResponse(buildMsgResponse(634, function (Gianfriaur\OpcuaPhpClient\Encoding\BinaryEncoder $e) {
            $e->writeInt32(1);
            $e->writeByte(0x01);
            $e->writeByte(Gianfriaur\OpcuaPhpClient\Types\BuiltinType::Int32->value);
            $e->writeInt32(2);
            $e->writeInt32(0);
        }));

        $client = setupConnectedClient($mock);
        setClientProperty($client, 'logger', $logger);
        setClientProperty($client, 'batchSize', 1);

        $results = $client->readMulti([
            ['nodeId' => 'i=2259'],
            ['nodeId' => 'i=2267'],
        ]);

        expect($results)->toHaveCount(2);

        $messages = array_column($logger->logs, 1);
        $hasBatchLog = false;
        foreach ($messages as $msg) {
            if (str_contains($msg, 'Splitting readMulti')) {
                $hasBatchLog = true;
                break;
            }
        }
        expect($hasBatchLog)->toBeTrue();
    });
});
