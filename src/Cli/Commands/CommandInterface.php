<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Cli\Commands;

use Gianfriaur\OpcuaPhpClient\Cli\Output\OutputInterface;
use Gianfriaur\OpcuaPhpClient\ClientBuilder;
use Gianfriaur\OpcuaPhpClient\OpcUaClientInterface;

/**
 * Contract for CLI commands.
 */
interface CommandInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getDescription(): string;

    /**
     * @return string
     */
    public function getUsage(): string;

    /**
     * @param OpcUaClientInterface|ClientBuilder $client Connected client or builder (for offline commands).
     * @param string[] $arguments
     * @param array<string, string|bool> $options
     * @param OutputInterface $output
     * @return int
     */
    public function execute(OpcUaClientInterface|ClientBuilder $client, array $arguments, array $options, OutputInterface $output): int;

    /**
     * @return bool
     */
    public function requiresConnection(): bool;
}
