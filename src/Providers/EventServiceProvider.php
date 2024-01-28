<?php

namespace Skillcraft\Referral\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Skillcraft\Referral\Listeners\RegisteredEventListener;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Registered::class => [
            RegisteredEventListener::class,
        ],
    ];
}
