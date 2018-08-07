<?php

namespace Mews\LaravelPos;

use Illuminate\Support\ServiceProvider;

/**
 * Class LaravelPosServiceProvider
 * @package Mews\LaravelPos
 */
class LaravelPosServiceProvider extends ServiceProvider {

    /**
     * Boot the service provider.
     *
     * @return null
     */
    public function boot()
    {
        // Config file publishes
        $this->publishes([
            __DIR__.'/../config/laravel-pos.php' => config_path('laravel-pos.php')
        ], 'config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Set App Alias
        $this->app->alias(LaravelPos::class, 'laravel-pos');

        // Bind LaravelPos
        $this->app->bind('laravelpos', function($app)
        {
            return new LaravelPos($app['Illuminate\Config\Repository']);
        });
    }

}
