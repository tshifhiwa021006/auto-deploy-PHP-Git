<?php

namespace AutoDeployPHP;

class Config
{
    private array $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    // Support dot notation keys like 'deployment.deploy_to'
    public function get(string $key, $default = null)
    {
        if ($key === '') {
            return $default;
        }
        $parts = explode('.', $key);
        $value = $this->data;
        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }

    public function set(string $key, $value): void
    {
        $parts = explode('.', $key);
        $ref = &$this->data;
        foreach ($parts as $part) {
            if (!isset($ref[$part]) || !is_array($ref[$part])) {
                $ref[$part] = [];
            }
            $ref = &$ref[$part];
        }
        $ref = $value;
    }

    public function all(): array
    {
        return $this->data;
    }
}
