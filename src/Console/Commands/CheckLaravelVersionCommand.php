<?php

namespace YourVendor\LaravelVersionChecker\Console\Commands;

use Illuminate\Console\Command;
use YourVendor\LaravelVersionChecker\Services\LaravelVersionChecker;

class CheckLaravelVersionCommand extends Command
{
    protected $signature = 'laravel:check-version';
    protected $description = 'Check for new Laravel framework releases and send Telegram notification';

    protected $versionChecker;

    public function __construct(LaravelVersionChecker $versionChecker)
    {
        parent::__construct();
        $this->versionChecker = $versionChecker;
    }

    public function handle()
    {
        $this->info('Checking for Laravel updates...');
        $this->versionChecker->checkForUpdate();
        $this->info('Version check completed.');
    }
}