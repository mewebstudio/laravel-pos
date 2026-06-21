<?php

namespace Mews\LaravelPos\Tests;

use Illuminate\Support\Facades\Event;
use Mews\LaravelPos\EventDispatcher\EventDispatcher;
use Orchestra\Testbench\TestCase;

class EventDispatcherTest extends TestCase
{
    public function test_dispatch_returns_the_event(): void
    {
        $dispatcher = new EventDispatcher();
        $event = new \stdClass();

        $result = $dispatcher->dispatch($event);

        $this->assertSame($event, $result);
    }

    public function test_dispatch_fires_laravel_event(): void
    {
        Event::fake();

        $dispatcher = new EventDispatcher();
        $event = new \stdClass();
        $dispatcher->dispatch($event);

        Event::assertDispatched(\stdClass::class);
    }
}
