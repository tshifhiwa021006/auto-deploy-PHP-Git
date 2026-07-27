<?php

namespace AutoDeployPHP;

class Security
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Verify GitHub webhook signature
     */
    public function verifySignature(string $payload, string $signature): bool
    {
        if (!$this->config->get('github.verify_signature', true)) {
            return true;
        }

        $secret = $this->config->get('github.webhook_secret', '');
        if (empty($secret)) {
            return false;
        }

        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }

    /**
     * Verify request comes from GitHub IP
     */
    public function verifyIp(string $ip): bool
    {
        if (!$this->config->get('security.restrict_to_github_ips', true)) {
            return true;
        }

        $allowed = $this->config->get('security.allowed_ips', []);
        if (empty($allowed)) {
            $allowed = $this->getGitHubIps();
        }

        foreach ($allowed as $allowed_ip) {
            if ($this->ipInRange($ip, $allowed_ip)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is in CIDR range
     */
    private function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        [$subnet, $bits] = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - (int)$bits);
        $subnet &= $mask;

        return ($ip & $mask) === $subnet;
    }

    /**
     * Get GitHub's IP ranges
     */
    private function getGitHubIps(): array
    {
        return [
            '140.82.112.0/20',
            '143.55.64.0/20',
            '185.199.108.0/22',
            '2606:4700:4700::/32',
        ];
    }

    /**
     * Validate branch name
     */
    public static function isValidBranch(string $branch): bool
    {
        return preg_match('/^[a-zA-Z0-9\/_\-.]+$/', $branch) === 1;
    }
}
