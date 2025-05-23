# Laravel Version Checker

A Laravel package to check for new Laravel framework releases, verify PHP version and extension compatibility, and notify via Telegram. It also ensures the server’s PHP extensions are sufficient for the project’s Laravel version.

## Installation

1. Install the package via Composer:

   ```bash
   composer require version-checker/laravel-version-checker
   ```

2. Publish the configuration file:

   ```bash
   php artisan vendor:publish --tag=config
   ```

3. Update your `.env` file:

   ```env
   VERSION_CHECKER_TELEGRAM_CHAT_ID=your_chat_id
   VERSION_CHECKER_GITHUB_TOKEN=your_github_token
   VERSION_CHECKER_SCHEDULE_ENABLED=true
   VERSION_CHECKER_SCHEDULE_CRON="0 0 * * *"
   ```

## Setup

### Telegram Bot

- Create a bot via @BotFather and get the token.
- Start a chat with the bot and get the chat ID using `https://api.telegram.org/bot<token>/getUpdates`.

### GitHub Token

- Create a personal access token with `repo` scope in GitHub Settings.

### Run the Command

```bash
php artisan laravel:check-version
```

The command checks:
- The latest Laravel release via GitHub API.
- The project’s installed Laravel version (from `composer.json`).
- PHP version compatibility with the latest Laravel version.
- Whether the server’s PHP extensions are sufficient (all required extensions are enabled).
- Sends a Telegram notification with a detailed compatibility report, including installation suggestions for missing extensions.

The command is scheduled to run daily by default (configurable via `VERSION_CHECKER_SCHEDULE_CRON`).

## License

MIT
