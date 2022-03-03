<?php

namespace Brausepulver\EloquentToGraphQL\Listeners;

use Illuminate\Database\Events\MigrationsEnded;
use Illuminate\Support\Facades\{Artisan, Config};

class RunCommand
{
    public function handle(MigrationsEnded $event)
    {
        $command = Config::get('eloquent_to_graphql.run_after_migrations');

        if (!empty($command)) {
            Artisan::call($command);
        }
    }
}
