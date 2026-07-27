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

## Quick Start (5 Minutes)

```bash
# 1. Download
git clone https://github.com/tshifhiwa021006/auto-deploy-PHP-Git.git
cd auto-deploy-PHP-Git

# 2. Install
composer install

# 3. Setup
cp config.example.php config.php
nano config.php  # Edit with YOUR settings

# 4. Create webhook file
mkdir -p public
cat > public/webhook.php << 'EOF'
<?php
require_once __DIR__ . '/../vendor/autoload.php';
use AutoDeployPHP\WebhookHandler;
use AutoDeployPHP\Config;
$config = Config::load(__DIR__ . '/../config.php');
$handler = new WebhookHandler($config);
echo $handler->handle();
EOF

# 5. Tell GitHub about it (see Step 5 below)
```

Done! Push code and watch it deploy automatically.

## How to Set It Up (Detailed)

### Step 1: Download the Tool

```bash
git clone https://github.com/tshifhiwa021006/auto-deploy-PHP-Git.git
cd auto-deploy-PHP-Git
```

### Step 2: Install Libraries

```bash
composer install
```

This downloads all the code libraries this tool needs to work.

### Step 3: Set Up Your Settings

Copy the example settings file:

```bash
cp config.example.php config.php
```

Edit `config.php` with your information. Here's what each setting means:

```php
<?php
return [
    'deployment' => [
        // Your GitHub repo URL (find this on GitHub)
        'repository' => 'git@github.com:username/your-repo.git',
        
        // Which branch to deploy (main, master, develop, etc)
        'branch' => 'main',
        
        // Where to upload on your server
        'deploy_to' => '/var/www/html/app',
        
        // How many old versions to keep (in case you need to go back)
        'keep_releases' => 5,
    ],
    
    'github' => [
        // This is a secret code - make it hard to guess (at least 32 characters)
        'webhook_secret' => 'your-very-secret-key-here-make-it-long',
        
        // Get this from GitHub: Settings > Developer settings > Personal access tokens
        'token' => 'github_pat_xxxxxxxxxxxxx',
    ],
    
    'notifications' => [
        // Get this from Slack: https://api.slack.com/apps (optional)
        'slack_webhook' => 'https://hooks.slack.com/services/YOUR/WEBHOOK/URL',
        
        // Where to send email alerts
        'email' => 'admin@example.com',
    ],
    
    'pre_deploy_hooks' => [
        // Commands to run BEFORE uploading (optional)
        'scripts/before-deploy.sh',
    ],
    
    'post_deploy_hooks' => [
        // Commands to run AFTER uploading (optional)
        'scripts/after-deploy.sh',
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

This file is what GitHub talks to. When you push code, GitHub sends a message to this file.

### Step 5: Tell GitHub to Notify Your Server

1. Go to your GitHub repository on github.com
2. Click **Settings** (top right)
3. Click **Webhooks** (left side menu)
4. Click **Add webhook** (green button)
5. Fill in the form:
   - **Payload URL:** `https://your-website.com/webhook.php` (replace with YOUR domain)
   - **Content type:** `application/json`
   - **Secret:** Copy your secret from `config.php`
   - **Which events:** Select `push` and `pull_request`
6. Click **Add webhook** button

Now GitHub will automatically tell your server every time you push code!

## How to Use It

### Automatic Deployment (Recommended)

This is the easiest way. Just push your code normally:

```bash
git push origin main
```

Your website updates automatically in about 30 seconds. Check Slack or email for confirmation.

### Manual Deployment

If you want to deploy without pushing:

```bash
php bin/deploy.php --branch main --environment production
```

### Check Deployment Status

See what version is running right now:

```bash
php bin/status.php
```

### Go Back to Previous Version

If something breaks after deployment:

```bash
php bin/rollback.php
```

Done. Your old version is back online.

### See Previous Deployments

View the last 20 deployments:

```bash
php bin/history.php --limit 20
```

### Check If Website is Working

Run automatic checks:

```bash
php bin/health-check.php
```

## Folder Structure

Here's what each folder/file does:

```
auto-deploy-PHP-Git/
├── bin/                    # Commands you can run
│   ├── deploy.php          # Deploy manually
│   ├── rollback.php        # Go back to old version
│   ├── status.php          # Check current version
│   ├── history.php         # See past deployments
│   └── health-check.php    # Test if it works
│
├── src/                    # The actual tool code
│   ├── WebhookHandler.php  # Listens for GitHub notifications
│   ├── Deployer.php        # Does the uploading
│   ├── Rollback.php        # Goes back to old version
│   ├── Notification.php    # Sends Slack/email messages
│   ├── Config.php          # Reads your settings
│   └── Security.php        # Checks if it's really GitHub
│
├── public/                 # Web files (your server sees these)
│   └── webhook.php         # GitHub sends messages here
│
├── scripts/                # Extra commands that run
│   ├── before-deploy.sh    # Runs before uploading
│   └── after-deploy.sh     # Runs after uploading
│
├── tests/                  # Tests to check if it works
│   ├── Unit/               # Small tests
│   └── Integration/        # Tests that work together
│
├── config.example.php      # Example settings (copy this)
├── config.php              # YOUR settings (don't share!)
├── composer.json           # List of libraries needed
├── phpunit.xml             # Testing settings
└── README.md               # This file
```

## What Happens When You Push Code

Here's step-by-step what happens:

```
1. You type: git push
   ↓
2. GitHub receives your code
   ↓
3. GitHub sends a message to your server (webhook)
   ↓
4. Your server receives: "Hey, new code is here!"
   ↓
5. Server checks: "Is this really from GitHub?" (using secret)
   ↓
6. Server runs: scripts/before-deploy.sh (prep work)
   ↓
7. Server downloads the new code from GitHub
   ↓
8. Server creates a new folder with today's date (releases/2026-07-27-143022/)
   ↓
9. Server runs your code in that folder
   ↓
10. Server makes a shortcut called "current" pointing to new folder
    (This is why it's fast - just changing a shortcut, not copying files)
   ↓
11. Server runs: scripts/after-deploy.sh (cleanup)
   ↓
12. Server checks: "Is the website working?" (health check)
   ↓
13. Server sends Slack message: "Deployment successful!"
   ↓
14. DONE! Website is updated
```

Typical time: 30-60 seconds

## Settings Explained

### Deployment Settings

| Setting | What It Does | Example |
|---------|-------------|---------|
| `repository` | Your GitHub repo URL | `git@github.com:username/myapp.git` |
| `branch` | Which branch to deploy | `main` or `production` |
| `deploy_to` | Where to upload files | `/var/www/html/myapp` |
| `keep_releases` | How many old versions to keep | `5` (keeps last 5 versions) |

### GitHub Settings

| Setting | What It Does | Where to Get It |
|---------|-------------|-----------------|
| `webhook_secret` | Secret code to verify it's GitHub | Make it up (32+ characters) |
| `token` | GitHub access token | GitHub Settings > Developer settings > Personal access tokens |

### Notification Settings

| Setting | What It Does | How to Get It |
|---------|-------------|---------------|
| `slack_webhook` | Send Slack messages | https://api.slack.com/apps |
| `email` | Send email alerts | Any email address |

## Before and After Scripts

### Before Uploading (`scripts/before-deploy.sh`)

This runs BEFORE your website gets the new code. Use it to prepare:

```bash
#!/bin/bash
set -e

echo "Preparing to deploy..."

# Update the database (if using Laravel)
php artisan migrate --force

# Clear old cached data
php artisan cache:clear

# Build new CSS/JavaScript
npm run build

# Compress images
php artisan optimize:images
```

### After Uploading (`scripts/after-deploy.sh`)

This runs AFTER your website has the new code. Use it to clean up:

```bash
#!/bin/bash
set -e

echo "Deployment complete, cleaning up..."

# Warm up the cache so site is fast
php artisan cache:warmup

# Restart background workers
supervisorctl restart all

# Tell monitoring service it's done
curl -X POST https://your-website.com/api/deployment-done

# Send success notification
echo "Deployment finished successfully!"
```

## Environment File (For Secrets)

Instead of putting secrets in `config.php`, create `.env` file:

```bash
# .env file (NEVER commit this to GitHub!)
GITHUB_TOKEN=github_pat_xxxxxxxxxxxxx
GITHUB_WEBHOOK_SECRET=your-super-secret-key
SLACK_WEBHOOK=https://hooks.slack.com/services/YOUR/WEBHOOK
EMAIL_ADDRESS=admin@example.com
DEPLOY_USER=deploy_user
DEPLOY_HOST=production.example.com
DEPLOY_PATH=/var/www/html/app
```

Then in `config.php`:

```php
<?php
return [
    'deployment' => [
        'repository' => $_ENV['GITHUB_REPO'] ?? 'git@github.com:user/repo.git',
        'token' => $_ENV['GITHUB_TOKEN'],
        // ... rest of config
    ],
];
```

## Security Tips

- **Use HTTPS** - Always upload to `https://` not `http://`
- **Keep secrets secret** - Never put them in `config.php`, use `.env`
- **Use strong secrets** - Make `webhook_secret` at least 32 characters, mix letters, numbers, symbols
- **Limit GitHub access** - Use a Personal Access Token with minimal permissions
- **Check logs** - Look at deployment logs for anything suspicious
- **Update your server** - Keep PHP and all libraries up to date
- **Restrict IPs** - Only allow GitHub IPs to reach your webhook (optional)

## Testing

Make sure everything works before deploying:

```bash
# Run all tests
composer test

# Check code quality
composer stan

# Fix code style issues
composer fix

# Run all checks at once
composer check
```

## Problems and Solutions

### My website isn't updating after I push

**Solution 1:** Check if GitHub can reach your server
- Go to GitHub repo > Settings > Webhooks
- Click your webhook
- Scroll down to "Recent Deliveries"
- If it's red, click it to see the error

**Solution 2:** Check your URL
- Make sure `webhook.php` is accessible at `https://your-site.com/webhook.php`
- Test in your browser (you'll see a message if it works)

**Solution 3:** Check your secret
- Go to GitHub webhook settings
- Make sure "Secret" matches exactly in your `config.php`

**Solution 4:** Check firewall
- Make sure your server allows incoming requests from GitHub IPs

### Deployment fails with an error

```bash
# 1. Check what went wrong
cat deploy/logs/latest.log

# 2. Check SSH key permissions (must be 600)
chmod 600 ~/.ssh/id_rsa

# 3. Make sure PHP can write to the folder
chmod 755 /var/www/html/app

# 4. Try deploying manually with details
php bin/deploy.php --branch main --environment production -v
```

### Can't go back to old version (rollback)

```bash
# 1. Check if old versions exist
ls releases/

# 2. Check current link
ls -l current/

# 3. If releases folder is empty, you can't go back (keep more versions next time)
# Edit config.php: 'keep_releases' => 10
```

### Website goes offline during deployment

This shouldn't happen because we use symlinks (fast shortcuts). If it does:

```bash
# Check what happened
cat deploy/logs/latest.log

# It might be your "before" or "after" scripts taking too long
# Speed them up or remove them temporarily
```

### Deployment works but website shows old code

```bash
# Clear browser cache
# Or try in a private/incognito window

# Check if you're on the right server
php bin/status.php

# Restart PHP
sudo systemctl restart php-fpm

# Or (Apache)
sudo systemctl restart apache2
```

## Real Examples

### Example 1: Simple Blog

Deploy your blog every time you write a new post:

```bash
# Write a post
echo "# My New Blog Post" > posts/my-post.md

# Push to GitHub
git add posts/
git commit -m "New blog post"
git push origin main

# Website updates automatically (30 seconds later)
# Blog shows your new post
```

### Example 2: Fix a Bug in Production

Bug found in production:

```bash
# Fix the bug locally
nano src/bug-fix.php

# Test it works
composer test

# Push the fix
git add src/
git commit -m "Fix: Bug in production"
git push origin main

# Website auto-updates with the fix
# Users see it immediately
```

### Example 3: Undo a Bad Deployment

Something broke on the website:

```bash
# Go back to the old version
php bin/rollback.php

# Website is back to normal (instant)

# Now fix the bug locally
nano src/bad-feature.php

# Push the fix
git push origin main

# Website updates again with the fix
```

### Example 4: Deploy Different Code to Different Servers

In your `config.php`:

```php
'deployment' => [
    'repository' => 'git@github.com:company/app.git',
    'branch' => $_ENV['DEPLOY_BRANCH'] ?? 'main',  // Different branches
    'deploy_to' => $_ENV['DEPLOY_PATH'],           // Different servers
],
```

Then deploy to different servers:

```bash
# Deploy to staging
DEPLOY_BRANCH=develop DEPLOY_PATH=/var/www/staging php bin/deploy.php

# Deploy to production
DEPLOY_BRANCH=main DEPLOY_PATH=/var/www/production php bin/deploy.php
```

## Frequently Asked Questions

**Q: What if deployment takes too long?**
A: Check your `before-deploy.sh` and `after-deploy.sh` scripts. Optimize database migrations or remove unnecessary tasks.

**Q: Can I deploy multiple times per second?**
A: Yes, but each one waits for the previous to finish. Not recommended.

**Q: What if I have a big website?**
A: Increase `keep_releases` to keep more backups, and make sure you have enough disk space.

**Q: Can I deploy without GitHub?**
A: Yes, run `php bin/deploy.php` manually anytime.

**Q: What if my website has database changes?**
A: Put migrations in `before-deploy.sh`, they'll run automatically.

**Q: Can I test it first?**
A: Yes, create a staging environment with a different `deploy_to` path and deploy there first.

**Q: What if I need to run the same deployment twice?**
A: Just run the command twice, it's safe.

**Q: How much disk space do I need?**
A: About 5x your application size (to keep old versions). Adjust `keep_releases` to save space.

## Advanced Features

### Custom Deploy Scripts

Create your own deployer:

```php
<?php
class MyDeployer extends AutoDeployPHP\Deployer {
    protected function beforeDeploy() {
        // Your custom code
        $this->runCommand('custom-backup.sh');
    }
}
```

### Conditional Deployments

Only deploy when certain conditions are met:

```php
$deployer->onlyIf(function($payload) {
    // Only deploy main branch
    return $payload['ref'] === 'refs/heads/main' 
        // Except for dependabot
        && $payload['pusher']['name'] !== 'dependabot';
});
```

### Multiple Environments

Deploy to different servers based on branch:

```php
// Deploy develop branch to staging
// Deploy main branch to production
// Deploy hotfix branch to both

// Put this in your webhook handler
if ($branch === 'develop') {
    deployTo('staging');
} elseif ($branch === 'main') {
    deployTo('production');
}
```

## Support and Help

Found a bug? Have ideas? Missing something?

- Open an issue on GitHub: https://github.com/tshifhiwa021006/auto-deploy-PHP-Git/issues
- Include what you tried and what went wrong
- Include error messages and logs

## Contributing

Want to improve this? We'd love help!

- Fork the repo
- Make your changes
- Test it works
- Send a pull request

## License

MIT License - You can use this for anything (personal, business, education, etc.)

---

**Version:** 1.0.0
**Last Updated:** 2026-07-27
**Created by:** Auto Deploy PHP Contributors

---

## Checklist: You're Ready to Go

- [ ] Downloaded the tool
- [ ] Installed dependencies with `composer install`
- [ ] Created and edited `config.php`
- [ ] Created `public/webhook.php`
- [ ] Added webhook in GitHub Settings
- [ ] Tested by pushing code
- [ ] Checked Slack/email for notification
- [ ] Celebrated your automated deployment!
