#!/usr/bin/env php
<?php
require_once __DIR__ . '/../vendor/autoload.php';

use AutoDeployPHP\Config;

// Load config
$configPath = __DIR__ . '/../config.php';
$examplePath = __DIR__ . '/../config.example.php';
if (file_exists($configPath)) {
    $configArray = include $configPath;
} elseif (file_exists($examplePath)) {
    $configArray = include $examplePath;
} else {
    fwrite(STDERR, "Missing config.php and config.example.php\n");
    exit(1);
}

$config = new Config($configArray);
$deployTo = $config->get('deployment.deploy_to', sys_get_temp_dir());
$current = $deployTo . '/current';
$releasesDir = $deployTo . '/releases';

if (!is_dir($releasesDir)) {
    fwrite(STDERR, "No releases directory found: $releasesDir\n");
    exit(1);
}

$releases = array_values(array_diff(scandir($releasesDir, SCANDIR_SORT_DESCENDING), ['.', '..']));
if (count($releases) < 2) {
    fwrite(STDERR, "Not enough releases to rollback\n");
    exit(1);
}

// Determine current target and the previous release
$currentTarget = is_link($current) ? readlink($current) : null;

$previous = $releases[1] ?? null;
if ($previous === null) {
    fwrite(STDERR, "No previous release found\n");
    exit(1);
}

$previousPath = $releasesDir . '/' . $previous;
if (!is_dir($previousPath)) {
    fwrite(STDERR, "Previous release directory not found: $previousPath\n");
    exit(1);
}

// Atomically update current symlink
$tmp = $current . '.tmp';
if (is_link($tmp)) {
    unlink($tmp);
}
symlink($previousPath, $tmp);
if (is_link($current)) {
    unlink($current);
}
rename($tmp, $current);

echo "Rolled back to release: $previous\n";
