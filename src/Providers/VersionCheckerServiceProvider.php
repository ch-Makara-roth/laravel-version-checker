<?php

namespace YourVendor\LaravelVersionChecker\Providers;

use Illuminate\Support\ServiceProvider;
use YourVendor\LaravelVersionChecker\Services\LaravelVersionChecker;
use YourVendor\LaravelVersionChecker\Console\Commands\CheckLaravelVersionCommand;

class VersionCheckerServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Merge config
        $this->mergeConfigFrom(__DIR__ . '/../../config/version-checker.php', 'version-checker');

        // Register the service
        $this->app->singleton(LaravelVersionChecker::class, function ($app) {
            return new LaravelVersionChecker();
        });
    }

    public function boot()
    {
        // Publish config
        $this->publishes([
            __DIR__ . '/../../config/version-checker.php' => config_path('version-checker.php'),
        ], 'config');

        // Register command
        if ($this->app->runningInConsole()) {
            $this->commands([
                CheckLaravelVersionCommand::class,
            ]);

            // Schedule the command if enabled
            $this->app->booted(function () {
                if (config('version-checker.schedule.enabled')) {
                    $schedule = $this->app->make(\Illuminate\Console\Scheduling\Schedule::class);
                    $schedule->command('laravel:check-version')
                             ->cron(config('version-checker.schedule.cron'))
                             ->withoutOverlapping();
                }
            });
        }
    }
}