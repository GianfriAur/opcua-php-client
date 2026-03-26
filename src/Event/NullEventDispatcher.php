<?php

declare(strict_types=1);

namespace PhpOpcua\Client\Event;

use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * A no-op event dispatcher that discards all events.
 *
 * Used as the default dispatcher when no custom implementation is configured,
 * ensuring zero overhead when event dispatching is not needed.
 *
 * @see \PhpOpcua\Client\Client\ManagesEventDispatcherTrait
 */
readonly class NullEventDispatcher implements EventDispatcherInterface
{
    /**
     * {@inheritDoc}
     */
    public function dispatch(object $event): object
    {
        return $event;
    }
}
