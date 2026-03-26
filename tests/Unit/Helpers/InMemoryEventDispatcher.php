<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Tests\Unit\Helpers;

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * A simple in-memory event dispatcher for testing purposes.
 *
 * Collects all dispatched events in an array for assertion.
 */
class InMemoryEventDispatcher implements EventDispatcherInterface
{
    /** @var object[] */
    private array $events = [];

    public function dispatch(object $event): object
    {
        $this->events[] = $event;

        return $event;
    }

    /**
     * @return object[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * @template T of object
     * @param class-string<T> $eventClass
     * @return T[]
     */
    public function getEventsOfType(string $eventClass): array
    {
        return array_values(array_filter($this->events, fn (object $e) => $e instanceof $eventClass));
    }

    public function hasEvent(string $eventClass): bool
    {
        return ! empty($this->getEventsOfType($eventClass));
    }

    public function reset(): void
    {
        $this->events = [];
    }
}
