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
        Log::debug('LaravelVersionChecker initialized', [
            'telegram_chat_id' => $this->telegramChatId,
            'github_api_url' => $this->githubApiUrl
        ]);
    }

    /**
     * Check for new Laravel release and send Telegram notification if found
     */
    public function checkForUpdate()
    {
        try {
            // Get the latest release from GitHub
            Log::debug('Fetching latest Laravel release from GitHub');
            $response = Http::withHeaders([
                'Accept' => 'application/vnd.github.v3+json',
                'Authorization' => 'Bearer ' . config('version-checker.github.token'),
            ])->get($this->githubApiUrl);

            if ($response->failed()) {
                Log::error('Failed to fetch Laravel releases', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
                return;
            }

            $release = $response->json();
            Log::debug('GitHub API response', ['release' => $release]);

            $latestVersion = $release['tag_name'] ?? null;

            if (!$latestVersion) {
                Log::error('No version tag found in GitHub response');
                return;
            }

            // Clean version string (remove 'v' prefix if present)
            $latestVersion = ltrim($latestVersion, 'v');
            Log::debug('Processed latest version', ['version' => $latestVersion]);

            // Check if this is a new version
            $cachedVersion = Cache::get($this->cacheKey);
            Log::debug('Cache check', [
                'cached_version' => $cachedVersion,
                'latest_version' => $latestVersion
            ]);

            if ($cachedVersion !== $latestVersion) {
                // Store new version in cache
                Cache::put($this->cacheKey, $latestVersion, now()->addDays(7));
                Log::debug('Cached new version', ['version' => $latestVersion]);

                // Check compatibility
                $compatibilityMessage = $this->checkCompatibility($latestVersion);

                // Prepare notification message
                $message = "🚀 *New Laravel Release Detected!*\n" .
                          "Version: {$latestVersion}\n" .
                          "Release Notes: {$release['html_url']}\n\n" .
                          $compatibilityMessage;

                // Send Telegram notification
                Log::debug('Sending Telegram notification', [
                    'chat_id' => $this->telegramChatId,
                    'message' => $message
                ]);
                try {
                    Telegram::sendMessage([
                        'chat_id' => $this->telegramChatId,
                        'text' => $message,
                        'parse_mode' => 'Markdown',
                    ]);
                    Log::info("Telegram notification sent for Laravel version {$latestVersion}");
                } catch (\Exception $e) {
                    Log::error('Failed to send Telegram notification', [
                        'error' => $e->getMessage()
                    ]);
                }
            } else {
                Log::info('No new Laravel version detected', [
                    'cached_version' => $cachedVersion
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error checking Laravel version', [
                'error' => $e->getMessage()
            ]);
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

        Log::debug('Compatibility check result', ['message' => $message]);
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
                $cleanVersion = str_replace('^', '', $laravelVersion);
                Log::debug('Project Laravel version', ['version' => $cleanVersion]);
                return $cleanVersion;
            }
            Log::warning('composer.json not found');
            return 'Unknown';
        } catch (\Exception $e) {
            Log::error('Error reading composer.json', ['error' => $e->getMessage()]);
            return 'Unknown';
        }
    }
}