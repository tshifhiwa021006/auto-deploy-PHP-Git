# Auto Deploy PHP Git

A professional automatic PHP deployment system with GitHub webhooks, secure rollback capabilities, real-time notifications, and comprehensive security features.

## Features

- ✅ **GitHub Webhook Integration** — Automatic deployments on push/pull request events
- ✅ **Zero-Downtime Deployments** — Symlink-based release strategy
- ✅ **Rollback Support** — One-command rollback to previous releases
- ✅ **Notifications** — Slack, email, and webhook notifications
- ✅ **Security** — SSH key verification, signature validation, environment encryption
- ✅ **Logging & Monitoring** — Detailed deployment logs and history
- ✅ **Pre/Post Hooks** — Custom scripts before/after deployment
- ✅ **Health Checks** — Automatic deployment verification
- ✅ **Multi-Branch Support** — Deploy different branches to different environments

## Requirements

- PHP >= 7.4
- PHP Extensions: `json`, `curl`, `zip`, `openssl`
- SSH access to production server
- Git installed
- Composer (for dependency management)

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/tshifhiwa021006/auto-deploy-PHP-Git.git
cd auto-deploy-PHP-Git
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Deployment

Copy the example configuration:

```bash
cp config.example.php config.php
```

Edit `config.php` with your deployment settings:

```php
<?php
return [
    'deployment' => [
        'repository' => 'git@github.com:username/your-repo.git',
        'branch' => 'main',
        'deploy_to' => '/var/www/html/app',
        'keep_releases' => 5,
    ],
    'github' => [
        'webhook_secret' => 'your-webhook-secret',
        'token' => 'github-personal-access-token',
    ],
    'notifications' => [
        'slack_webhook' => 'https://hooks.slack.com/services/YOUR/WEBHOOK/URL',
        'email' => 'admin@example.com',
    ],
    'pre_deploy_hooks' => [
        'scripts/before-deploy.sh',
    ],
    'post_deploy_hooks' => [
        'scripts/after-deploy.sh',
    ],
];
```

### 4. Create Web Entry Point

Create `public/webhook.php`:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use AutoDeployPHP\WebhookHandler;
use AutoDeployPHP\Config;

$config = Config::load(__DIR__ . '/../config.php');
$handler = new WebhookHandler($config);

echo $handler->handle();
```

### 5. Set Up GitHub Webhook

1. Go to your GitHub repository settings
2. Navigate to **Settings > Webhooks > Add webhook**
3. Set **Payload URL** to: `https://your-domain.com/webhook.php`
4. Set **Content type** to: `application/json`
5. Set **Secret** to the value from your `config.php`
6. Select events: `push` and `pull_request`
7. Click **Add webhook**

## Usage

### Manual Deployment

```bash
php bin/deploy.php --branch main --environment production
```

### View Deployment Status

```bash
php bin/status.php
```

### Rollback to Previous Release

```bash
php bin/rollback.php
```

### View Deployment History

```bash
php bin/history.php --limit 20
```

### Run Health Check

```bash
php bin/health-check.php
```

## Directory Structure

```
auto-deploy-PHP-Git/
├── bin/                    # CLI entry points
├── src/                    # Source code
│   ├── WebhookHandler.php
│   ├── Deployer.php
│   ├── Rollback.php
│   ├── Notification.php
│   ├── Config.php
│   └── Security.php
├── public/                 # Web entry points
│   └── webhook.php
├── scripts/                # Deployment hooks
│   ├── before-deploy.sh
│   └── after-deploy.sh
├── tests/                  # Test suite
│   ├── Unit/
│   └── Integration/
├── config.example.php      # Example configuration
├── composer.json
└── phpunit.xml
```

## Configuration Options

### Deployment

| Option | Type | Description |
|--------|------|-------------|
| `repository` | string | Git repository URL |
| `branch` | string | Default branch to deploy |
| `deploy_to` | string | Absolute path on server |
| `keep_releases` | int | Number of releases to keep |

### Security

| Option | Type | Description |
|--------|------|-------------|
| `webhook_secret` | string | GitHub webhook secret |
| `verify_ssl` | bool | Verify SSL certificates |
| `allowed_ips` | array | Restrict webhook to IPs |

### Notifications

| Option | Type | Description |
|--------|------|-------------|
| `slack_webhook` | string | Slack webhook URL |
| `email` | string | Email for notifications |

## Deployment Flow

1. **Push to GitHub** → Webhook triggered
2. **Webhook Received** → Request validated
3. **Pre-Deploy Hooks** → Run before deployment
4. **Code Fetch** → Clone/pull latest code
5. **Release Creation** → Create timestamped release
6. **Current Link Update** → Switch to new release (atomic)
7. **Post-Deploy Hooks** → Run after deployment
8. **Health Check** → Verify deployment success
9. **Notification** → Send status to Slack/Email
10. **Logging** → Record in history

## Pre/Post Hook Scripts

### Before Deploy (`scripts/before-deploy.sh`)

```bash
#!/bin/bash
set -e

echo "Running pre-deployment tasks..."

# Run database migrations
php artisan migrate --force

# Clear cache
php artisan cache:clear

# Compile assets
npm run build
```

### After Deploy (`scripts/after-deploy.sh`)

```bash
#!/bin/bash
set -e

echo "Running post-deployment tasks..."

# Warm up cache
php artisan cache:warmup

# Restart queues
supervisorctl restart all

# Send deployment notification
curl -X POST https://your-domain.com/api/deployment-complete
```

## Testing

Run the test suite:

```bash
composer test
```

Run static analysis:

```bash
composer stan
```

Fix code style issues:

```bash
composer fix
```

Run all checks:

```bash
composer check
```

## Security Considerations

- ✅ Always use HTTPS for webhook URLs
- ✅ Store secrets in `.env` file (never commit)
- ✅ Restrict webhook to GitHub IPs
- ✅ Verify webhook signatures
- ✅ Use SSH keys for Git authentication
- ✅ Run deployment script with minimal privileges
- ✅ Monitor deployment logs for anomalies
- ✅ Keep deployment server updated and patched

## Troubleshooting

### Webhook Not Triggering

1. Check GitHub webhook delivery logs
2. Verify webhook URL is publicly accessible
3. Confirm secret matches in config
4. Check firewall rules

### Deployment Fails

1. Check deployment logs: `cat deploy/logs/latest.log`
2. Verify SSH key permissions: `chmod 600 ~/.ssh/id_rsa`
3. Ensure PHP has write permissions to deploy path
4. Run manual deployment with verbose flag

### Rollback Issues

1. Verify releases directory exists: `ls releases/`
2. Check symlink: `ls -l current/`
3. Run health check after rollback

## Support & Contributing

For issues, suggestions, or contributions, please open an issue on GitHub.

## License

MIT License - See LICENSE file for details.

## Examples

### Deploy Production on Main Branch Push

```php
// Automatically triggered via webhook
// Just push to main and deployment happens automatically
git push origin main
```

### Manual Production Deployment

```bash
php bin/deploy.php --branch main --environment production
```

### Rollback Last Deployment

```bash
php bin/rollback.php --environment production
```

## Environment Variables

Create `.env` file in project root:

```
GITHUB_TOKEN=github_token_here
SLACK_WEBHOOK=https://hooks.slack.com/services/...
DEPLOY_USER=deploy_user
DEPLOY_HOST=production.example.com
DEPLOY_PATH=/var/www/html/app
```

## Advanced Usage

### Custom Deployment Strategies

Extend `Deployer` class for custom logic:

```php
class CustomDeployer extends AutoDeployPHP\Deployer {
    protected function beforeDeploy() {
        // Custom logic
    }
}
```

### Conditional Deployments

Deploy only when specific conditions are met:

```php
$deployer->onlyIf(function($payload) {
    return $payload['ref'] === 'refs/heads/main' 
        && $payload['pusher']['name'] !== 'dependabot';
});
```

---

**Version:** 1.0.0  
**Last Updated:** 2026-07-27  
**Maintained by:** Auto Deploy PHP Contributors
