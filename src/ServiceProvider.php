<?php

namespace Brausepulver\EloquentToGraphQL;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/eloquent_to_graphql.php' => config_path('eloquent_to_graphql.php')
        ], 'config');

        $this->mergeConfigFrom(__DIR__.'/../config/eloquent_to_graphql.php', 'eloquent_to_graphql');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->commands([
            Console\GenerateGraphQLSchemaFromEloquentCommand::class
        ]);
    }
}
