# Auto Deploy PHP Git

Automatically update your website when you push code to GitHub. No manual uploads needed.

## What It Does

When you push code to GitHub, this tool automatically:
- Downloads your new code
- Updates your website
- Checks if everything works
- Sends you a message saying it's done

## What You Can Do With It

- Push code to GitHub, website updates automatically
- Get notified on Slack when your code goes live
- Go back to an old version if something breaks
- Run commands before and after updating
- Check if your website is working after each update
- Deploy different code to different servers
- View a history of all your deployments

## What You Need

- PHP 7.4 or higher
- Git installed on your computer and server
- Composer (for installing libraries)
- SSH access to your server (to upload files)
- A GitHub account

## How to Set It Up

### Step 1: Download the Tool

```bash
git clone https://github.com/tshifhiwa021006/auto-deploy-PHP-Git.git
cd auto-deploy-PHP-Git
```

### Step 2: Install Libraries

```bash
composer install
```

### Step 3: Set Up Your Settings

Copy the example settings file:

```bash
cp config.example.php config.php
```

Edit `config.php` and put in YOUR information:

```php
<?php
return [
    'deployment' => [
        'repository' => 'git@github.com:username/your-repo.git',  // Your GitHub repo
        'branch' => 'main',                                        // Branch to deploy
        'deploy_to' => '/var/www/html/app',                       // Where to upload
        'keep_releases' => 5,                                      // Keep last 5 versions
    ],
    'github' => [
        'webhook_secret' => 'your-webhook-secret',                // Secret code from GitHub
        'token' => 'github-personal-access-token',               // Your GitHub access token
    ],
    'notifications' => [
        'slack_webhook' => 'https://hooks.slack.com/...',         // For Slack messages
        'email' => 'admin@example.com',                           // For email alerts
    ],
    'pre_deploy_hooks' => [
        'scripts/before-deploy.sh',                              // Run before uploading
    ],
    'post_deploy_hooks' => [
        'scripts/after-deploy.sh',                               // Run after uploading
    ],
];
```

### Step 4: Create the Webhook File

Create a new file at `public/webhook.php`:

```php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use AutoDeployPHP\WebhookHandler;
use AutoDeployPHP\Config;

$config = Config::load(__DIR__ . '/../config.php');
$handler = new WebhookHandler($config);

echo $handler->handle();
```

### Step 5: Tell GitHub to Notify Your Server

1. Go to your GitHub repo
2. Click **Settings**
3. Click **Webhooks**
4. Click **Add webhook**
5. Paste your URL: `https://your-website.com/webhook.php`
6. Content type: `application/json`
7. Secret: Use the secret from your `config.php`
8. Select: `push` and `pull_request` events
9. Click **Add webhook**

Now GitHub will automatically notify your server whenever you push code.

## How to Use It

### Automatic Deployment (Recommended)

Just push your code:

```bash
git push origin main
```

Your website updates automatically.

### Manual Deployment

If you want to deploy manually:

```bash
php bin/deploy.php --branch main --environment production
```

### Check Deployment Status

```bash
php bin/status.php
```

### Go Back to Previous Version

If something breaks:

```bash
php bin/rollback.php
```

### See Previous Deployments

```bash
php bin/history.php --limit 20
```

### Check If Website is Working

```bash
php bin/health-check.php
```

## Folder Structure

```
auto-deploy-PHP-Git/
├── bin/                    # Commands you can run
├── src/                    # The actual tool code
│   ├── WebhookHandler.php  # Listens for GitHub notifications
│   ├── Deployer.php        # Does the uploading
│   ├── Rollback.php        # Goes back to old version
│   ├── Notification.php    # Sends messages
│   ├── Config.php          # Reads your settings
│   └── Security.php        # Checks if it's safe
├── public/                 # Web files
│   └── webhook.php         # GitHub talks to this
├── scripts/                # Commands that run
│   ├── before-deploy.sh    # Runs before uploading
│   └── after-deploy.sh     # Runs after uploading
├── tests/                  # Tests to check if it works
├── config.example.php      # Example settings
├── composer.json           # List of libraries needed
└── phpunit.xml             # Testing configuration
```

## What Happens When You Push Code

1. You type: `git push`
2. GitHub sends a message to your server
3. Your server receives the message
4. Server runs your "before" commands
5. Server downloads the new code
6. Server creates a new version folder
7. Server switches to the new version (super fast)
8. Server runs your "after" commands
9. Server checks if website is working
10. Server sends you a Slack/email message
11. Everything is done

## Settings Explained

| Setting | What It Does |
|---------|-------------|
| `repository` | Your GitHub repo URL |
| `branch` | Which branch to deploy (main, develop, etc) |
| `deploy_to` | Where to upload files on your server |
| `keep_releases` | How many old versions to keep |
| `webhook_secret` | Secret code to make sure it's really GitHub |
| `slack_webhook` | Where to send Slack messages |
| `email` | Email address for alerts |

## Before/After Scripts

### Before Uploading (`scripts/before-deploy.sh`)

Run commands before the website updates:

```bash
#!/bin/bash
set -e

echo "Getting ready to deploy..."

# Update the database
php artisan migrate --force

# Clear old cache
php artisan cache:clear

# Build new files
npm run build
```

### After Uploading (`scripts/after-deploy.sh`)

Run commands after the website updates:

```bash
#!/bin/bash
set -e

echo "Finished deploying..."

# Warm up cache
php artisan cache:warmup

# Restart services
supervisorctl restart all

# Tell other systems it's done
curl -X POST https://your-website.com/api/done
```

## Security Tips

- Always use HTTPS (the lock icon)
- Don't share your secret keys
- Put secrets in `.env` file, never in your code
- Only allow GitHub to trigger deployments
- Check logs regularly
- Keep your server updated

## Testing

Make sure everything works:

```bash
composer test
```

Check code quality:

```bash
composer stan
```

Fix code style:

```bash
composer fix
```

## Environment File

Create `.env` in your project root:

```
GITHUB_TOKEN=your_github_token_here
SLACK_WEBHOOK=https://hooks.slack.com/services/YOUR/WEBHOOK
DEPLOY_USER=deploy_user
DEPLOY_HOST=production.example.com
DEPLOY_PATH=/var/www/html/app
```

## Problems and Solutions

### Website Not Updating After I Push

1. Check GitHub webhook logs
2. Make sure your URL is correct
3. Check if your secret matches
4. Check your firewall settings

### Deployment Fails

1. Look at logs: `cat deploy/logs/latest.log`
2. Check SSH key: `chmod 600 ~/.ssh/id_rsa`
3. Make sure PHP can write to the upload folder
4. Try deploying manually with more details

### Can't Go Back to Old Version

1. Check if old versions exist: `ls releases/`
2. Check current link: `ls -l current/`
3. Run health check after going back

## Help

Found a bug or have ideas? Open an issue on GitHub.

## License

MIT License - You can use this for anything.

---

Version: 1.0.0
Last Updated: 2026-07-27
