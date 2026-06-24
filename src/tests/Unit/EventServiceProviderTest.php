<?php

namespace Tests\Unit;

use App\Providers\EventServiceProvider;
use Tests\TestCase;

class EventServiceProviderTest extends TestCase
{
    public function test_event_service_provider_boots_successfully(): void
    {
        $provider = new EventServiceProvider($this->app);
        $provider->boot();
        $this->assertTrue(true);
    }
}
