<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\Client;

use Closure;
use Gianfriaur\OpcuaPhpClient\Event\NullEventDispatcher;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Provides PSR-14 event dispatching support for the OPC UA client.
 *
 * Events are dispatched at key lifecycle points (connection, session, subscription,
 * read/write, browse, cache, retry) to allow consumer code to react to client activity.
 *
 * A {@see NullEventDispatcher} is used by default, ensuring zero overhead when no
 * custom dispatcher is configured. Event objects are lazily instantiated via closures
 * so that no allocation occurs unless a real dispatcher is listening.
 *
 * @see NullEventDispatcher
 * @see EventDispatcherInterface
 */
trait ManagesEventDispatcherTrait
{
    /**
     * @var EventDispatcherInterface The event dispatcher instance.
     */
    private EventDispatcherInterface $eventDispatcher;

    /**
     * Set the PSR-14 event dispatcher.
     *
     * @param EventDispatcherInterface $eventDispatcher The event dispatcher to use.
     * @return self
     *
     * @see EventDispatcherInterface
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher): self
    {
        $this->eventDispatcher = $eventDispatcher;
        return $this;
    }

    /**
     * Get the current event dispatcher.
     *
     * @return EventDispatcherInterface
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    /**
     * Dispatch an event or lazily create it from a closure.
     *
     * When the dispatcher is a {@see NullEventDispatcher}, this method returns immediately
     * without instantiating the event object, ensuring zero overhead.
     *
     * @param object $event The event object or a Closure that creates one.
     * @return void
     */
    private function dispatch(object $event): void
    {
        if ($this->eventDispatcher instanceof NullEventDispatcher) {
            return;
        }

        $this->eventDispatcher->dispatch($event instanceof Closure ? $event() : $event);
    }
}
