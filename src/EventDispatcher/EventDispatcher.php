<?php

namespace Mews\LaravelPos\EventDispatcher;

use Illuminate\Support\Facades\Event;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Laravel does not have a built service for Psr\EventDispatcher\EventDispatcherInterface.
 * So we are implemented our own.
 */
class EventDispatcher implements EventDispatcherInterface
{
    /**
     * @inheritDoc
     */
    public function dispatch(object $event)
    {
        Event::dispatch($event);
    }
}
