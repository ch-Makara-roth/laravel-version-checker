<?php

namespace VersionChecker\LaravelVersionChecker\Services;

use Telegram\Bot\Laravel\Facades\Telegram;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class LaravelVersionChecker
{
    protected $telegramChatId;
    protected $githubApiUrl;
    protected $cacheKey = 'latest_laravel_version';
    protected $requirements;

    public function __construct()
    {
        $this->telegramChatId = config('version-checker.telegram.chat_id');
        $this->githubApiUrl = config('version-checker.github.api_url');
        $this->requirements = config('version-checker.requirements');
    }

    /**
     * Check for new Laravel release and send Telegram notification if found
     */
    public function checkForUpdate()
    {
        try {
            // Get the latest release from GitHub
            $response = Http::withHeaders([
                'Accept' => 'application/vnd.github.v3+json',
                'Authorization' => 'Bearer ' . config('version-checker.github.token'),
            ])->get($this->githubApiUrl);

            if ($response->failed()) {
                Log::error('Failed to fetch Laravel releases: ' . $response->status());
                return;
            }

            $release = $response->json();
            $latestVersion = $release['tag_name'] ?? null;

            if (!$latestVersion) {
                Log::error('No version tag found in GitHub response');
                return;
            }

            // Clean version string (remove 'v' prefix if present)
            $latestVersion = ltrim($latestVersion, 'v');

            // Check if this is a new version
            $cachedVersion = Cache::get($this->cacheKey);

            if ($cachedVersion !== $latestVersion) {
                // Store new version in cache
                Cache::put($this->cacheKey, $latestVersion, now()->addDays(7));

                // Check compatibility
                $compatibilityMessage = $this->checkCompatibility($latestVersion);

                // Prepare notification message
                $message = "🚀 *New Laravel Release Detected!*\n" .
                          "Version: {$latestVersion}\n" .
                          "Release Notes: {$release['html_url']}\n\n" .
                          $compatibilityMessage;

                // Send Telegram notification
                Telegram::sendMessage([
                    'chat_id' => $this->telegramChatId,
                    'text' => $message,
                    'parse_mode' => 'Markdown',
                ]);

                Log::info("New Laravel version {$latestVersion} detected and notification sent");
            }
        } catch (\Exception $e) {
            Log::error('Error checking Laravel version: ' . $e->getMessage());
        }
    }

    /**
     * Check PHP version and extension compatibility for the Laravel version
     */
    protected function checkCompatibility($version)
    {
        $currentPhpVersion = phpversion();
        $minPhpVersion = 'Unknown';
        $requiredExtensions = [];
        $missingExtensions = [];
        $phpCompatible = false;
        $projectLaravelVersion = $this->getProjectLaravelVersion();

        // Find matching requirements
        foreach ($this->requirements as $laravelPattern => $reqs) {
            if (preg_match("/^{$laravelPattern}/", $version)) {
                $minPhpVersion = $reqs['php'];
                $requiredExtensions = $reqs['extensions'];
                $phpCompatible = version_compare($currentPhpVersion, $minPhpVersion, '>=');
                break;
            }
        }

        // Check for missing extensions
        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                $missingExtensions[] = $extension;
            }
        }

        // Build compatibility message
        $message = "*Project Laravel Version*\n" .
                   "Current Project: {$projectLaravelVersion}\n" .
                   "Latest Available: {$version}\n\n";

        $message .= "*PHP Compatibility*\n" .
                   "Current PHP: {$currentPhpVersion}\n" .
                   "Required PHP: {$minPhpVersion}\n" .
                   "PHP Compatible: " . ($phpCompatible ? '✅ Yes' : '❌ No') . "\n\n";

        $message .= "*Required Extensions*\n";
        if (empty($missingExtensions)) {
            $message .= "All required extensions are enabled ✅\n" .
                       "Server extensions are sufficient for Laravel {$version}\n";
        } else {
            $message .= "Missing extensions: " . implode(', ', $missingExtensions) . " ❌\n" .
                       "Enabled extensions: " . implode(', ', array_diff($requiredExtensions, $missingExtensions)) . "\n" .
                       "Server extensions are NOT sufficient for Laravel {$version}\n\n" .
                       "*Installation Suggestions (Ubuntu/Debian)*\n";
            foreach ($missingExtensions as $extension) {
                $message .= "- Install php-$extension: `sudo apt-get install php-$extension`\n";
            }
        }

        return $message;
    }

    /**
     * Get the project's installed Laravel version from composer.json
     */
    protected function getProjectLaravelVersion()
    {
        try {
            $composerJsonPath = base_path('composer.json');
            if (File::exists($composerJsonPath)) {
                $composerData = json_decode(File::get($composerJsonPath), true);
                $require = $composerData['require'] ?? [];
                $laravelVersion = $require['laravel/framework'] ?? 'Unknown';
                return str_replace('^', '', $laravelVersion); // Clean version constraint
            }
            return 'Unknown';
        } catch (\Exception $e) {
            Log::error('Error reading composer.json: ' . $e->getMessage());
            return 'Unknown';
        }
    }
}