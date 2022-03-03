<?php

namespace Brausepulver\EloquentToGraphQL;

use Illuminate\Database\Events\MigrationsEnded;

class EventServiceProvider extends \Illuminate\Foundation\Support\Providers\EventServiceProvider
{
    protected $listen = [
        MigrationsEnded::class => [
            Listeners\RunCommand::class,
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }
}
