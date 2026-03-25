<?php

declare(strict_types=1);

namespace Gianfriaur\OpcuaPhpClient\ClientBuilder;

use Gianfriaur\OpcuaPhpClient\Event\NullEventDispatcher;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Provides PSR-14 event dispatcher configuration for the OPC UA client.
 *
 * A {@see NullEventDispatcher} is used by default, ensuring zero overhead when no
 * custom dispatcher is configured.
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
}
