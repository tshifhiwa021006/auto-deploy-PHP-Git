<?php

namespace AutoDeployPHP;

class WebhookHandler
{
    private Config $config;
    private Logger $logger;
    private Security $security;

    public function __construct(Config $config, ?Logger $logger = null)
    {
        $this->config = $config;
        $logDir = $this->config->get('deployment.deploy_to', sys_get_temp_dir()) . '/deploy_logs';
        $this->logger = $logger ?? new Logger($logDir);
        $this->security = new Security($config);
    }

    /**
     * Handle incoming webhook and trigger deployment.
     * Returns a JSON string suitable for HTTP response.
     */
    public function handle(): string
    {
        try {
            $payload = file_get_contents('php://input');
            $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? $_SERVER['HTTP_X_HUB_SIGNATURE'] ?? '';
            $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

            // Basic validation
            if (!$this->security->verifySignature($payload, $signature)) {
                http_response_code(403);
                $this->logger->warning('Invalid webhook signature');
                return json_encode(['success' => false, 'message' => 'Invalid signature']);
            }

            if (!$this->security->verifyIp($remoteIp)) {
                http_response_code(403);
                $this->logger->warning('Request IP not allowed: ' . $remoteIp);
                return json_encode(['success' => false, 'message' => 'IP not allowed']);
            }

            $data = json_decode($payload, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                $this->logger->error('Invalid JSON payload');
                return json_encode(['success' => false, 'message' => 'Invalid JSON']);
            }

            // Determine branch from ref (e.g. refs/heads/main)
            $ref = $data['ref'] ?? ($data['pull_request']['base']['ref'] ?? null);
            if ($ref && strpos($ref, 'refs/heads/') === 0) {
                $branch = substr($ref, strlen('refs/heads/'));
            } else {
                // Fallback to configured branch
                $branch = $this->config->get('deployment.branch', 'main');
            }

            if (!Security::isValidBranch($branch)) {
                http_response_code(400);
                $this->logger->warning('Invalid branch name in payload: ' . $branch);
                return json_encode(['success' => false, 'message' => 'Invalid branch name']);
            }

            $this->logger->info('Webhook accepted for branch: ' . $branch);

            $logger = $this->logger;
            $deployer = new Deployer($this->config, $logger);
            $result = $deployer->deploy($branch);

            if (!empty($result['success'])) {
                http_response_code(200);
                return json_encode($result);
            }

            http_response_code(500);
            return json_encode($result);
        } catch (\Exception $e) {
            http_response_code(500);
            $this->logger->error('Webhook handler exception: ' . $e->getMessage());
            return json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
