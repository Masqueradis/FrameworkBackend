<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\OrderCreated;
use App\Listeners\SendOrderConfirmationEmail;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        OrderCreated::class => [
            SendOrderConfirmationEmail::class,
        ],
    ];

    public function boot(): void
    {
        //
    }
}
