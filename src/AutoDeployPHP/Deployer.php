<?php

namespace AutoDeployPHP;

class Deployer
{
    private Config $config;
    private Logger $logger;
    private string $deployPath;
    private string $releasesPath;
    private string $currentLink;

    public function __construct(Config $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->deployPath = $config->get('deployment.deploy_to');
        $this->releasesPath = $this->deployPath . '/releases';
        $this->currentLink = $this->deployPath . '/current';
    }

    /**
     * Execute deployment
     */
    public function deploy(string $branch = null): array
    {
        try {
            $branch = $branch ?? $this->config->get('deployment.branch', 'main');

            if (!Security::isValidBranch($branch)) {
                throw new \Exception("Invalid branch name: $branch");
            }

            $this->logger->info("Starting deployment of branch: $branch");

            // Create releases directory
            $this->ensureReleasesDir();

            // Create new release directory
            $releaseDir = $this->createRelease();
            $this->logger->info("Created release directory: $releaseDir");

            // Run pre-deployment hooks
            $this->runHooks($this->config->get('pre_deploy_hooks', []), $releaseDir);

            // Clone/pull repository
            $this->fetchCode($releaseDir, $branch);
            $this->logger->info("Code fetched successfully");

            // Update current symlink
            $this->updateCurrentLink($releaseDir);
            $this->logger->info("Current symlink updated to: $releaseDir");

            // Run post-deployment hooks
            $this->runHooks($this->config->get('post_deploy_hooks', []), $releaseDir);

            // Cleanup old releases
            $this->cleanupReleases();

            // Run health check
            $healthy = $this->healthCheck();

            $this->logger->info("Deployment completed successfully");

            return [
                'success' => true,
                'release' => basename($releaseDir),
                'path' => $releaseDir,
                'healthy' => $healthy,
                'message' => 'Deployment completed successfully',
            ];
        } catch (\Exception $e) {
            $this->logger->error("Deployment failed: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Ensure releases directory exists
     */
    private function ensureReleasesDir(): void
    {
        if (!is_dir($this->releasesPath)) {
            mkdir($this->releasesPath, 0755, true);
        }
    }

    /**
     * Create new release directory
     */
    private function createRelease(): string
    {
        $releaseDir = $this->releasesPath . '/' . date('YmdHis');
        mkdir($releaseDir, 0755, true);
        return $releaseDir;
    }

    /**
     * Fetch code from repository
     */
    private function fetchCode(string $releaseDir, string $branch): void
    {
        $repository = $this->config->get('deployment.repository');
        $timeout = $this->config->get('deployment.timeout', 300);

        if (is_dir($releaseDir . '/.git')) {
            $this->executeCommand(
                "cd {$releaseDir} && git pull origin {$branch}",
                $timeout
            );
        } else {
            $this->executeCommand(
                "git clone --branch {$branch} {$repository} {$releaseDir}",
                $timeout
            );
        }
    }

    /**
     * Update current symlink atomically
     */
    private function updateCurrentLink(string $releaseDir): void
    {
        $tempLink = $this->currentLink . '.tmp';

        if (is_link($tempLink)) {
            unlink($tempLink);
        }

        symlink($releaseDir, $tempLink);

        if (is_link($this->currentLink)) {
            unlink($this->currentLink);
        }
        rename($tempLink, $this->currentLink);
    }

    /**
     * Run deployment hooks
     */
    private function runHooks(array $hooks, string $releaseDir): void
    {
        foreach ($hooks as $hook) {
            $hookPath = $releaseDir . '/' . $hook;

            if (!file_exists($hookPath)) {
                $this->logger->warning("Hook not found: $hook");
                continue;
            }

            chmod($hookPath, 0755);
            $this->logger->info("Running hook: $hook");

            $output = $this->executeCommand("cd {$releaseDir} && bash {$hook}");
            $this->logger->info("Hook output: $output");
        }
    }

    /**
     * Execute system command
     */
    private function executeCommand(string $command, int $timeout = 300): string
    {
        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes
        );

        if (!is_resource($process)) {
            throw new \Exception("Failed to execute command: $command");
        }

        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $code = proc_close($process);

        if ($code !== 0) {
            throw new \Exception("Command failed: $command\nError: $error");
        }

        return $output;
    }

    /**
     * Cleanup old releases
     */
    private function cleanupReleases(): void
    {
        $keep = $this->config->get('deployment.keep_releases', 5);
        $releases = array_diff(
            scandir($this->releasesPath, SCANDIR_SORT_DESCENDING),
            ['.', '..']
        );

        $toDelete = array_slice($releases, $keep);

        foreach ($toDelete as $release) {
            $path = $this->releasesPath . '/' . $release;
            $this->removeDirectory($path);
            $this->logger->info("Deleted old release: $release");
        }
    }

    /**
     * Remove directory recursively
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);

        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }

    /**
     * Run health check
     */
    private function healthCheck(): bool
    {
        if (!$this->config->get('health_check.enabled', true)) {
            return true;
        }

        $url = $this->config->get('health_check.url');
        if (!$url) {
            return true;
        }

        $timeout = $this->config->get('health_check.timeout', 10);
        $retries = $this->config->get('health_check.retries', 3);
        $delay = $this->config->get('health_check.retry_delay', 2);
        $expected = $this->config->get('health_check.expected_status', 200);

        for ($i = 0; $i < $retries; $i++) {
            if ($i > 0) {
                sleep($delay);
            }

            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => $this->config->get('security.verify_ssl', true),
            ]);

            curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code == $expected) {
                $this->logger->info("Health check passed: $url ($code)");
                return true;
            }
        }

        $this->logger->warning("Health check failed after $retries attempts");
        return false;
    }
}
