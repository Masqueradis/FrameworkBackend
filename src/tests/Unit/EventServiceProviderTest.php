<?php

namespace Tests\Unit;

use App\Providers\EventServiceProvider;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class EventServiceProviderTest extends TestCase
{
    public function testEventServiceProviderBootsSuccessfully(): void
    {
        $provider = new EventServiceProvider($this->app);
        $provider->boot();
        $this->assertTrue(true);
    }
}
