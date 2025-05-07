<?php

namespace VersionChecker\LaravelVersionChecker\Console\Commands;

use Illuminate\Console\Command;
use VersionChecker\LaravelVersionChecker\Services\LaravelVersionChecker;

class CheckLaravelVersionCommand extends Command
{
    protected $signature = 'laravel:check-version';
    protected $description = 'Check for new Laravel framework releases and send Telegram notification';

    protected $VersionChecker;

    public function __construct(LaravelVersionChecker $VersionChecker)
    {
        parent::__construct();
        $this->VersionChecker = $VersionChecker;
    }

    public function handle()
    {
        $this->info('Checking for Laravel updates...');
        $this->VersionChecker->checkForUpdate();
        $this->info('Version check completed.');
    }
}