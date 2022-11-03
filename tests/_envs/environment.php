<?php

/**
 * environment.php
 *
 * Load extra variables from config files into codeception environment.
 */

// Attempt to load the Gibbon config.php file
$configPath = __DIR__ . '/../../config.php';
if (!file_exists($configPath)) {
    // If no config file exists, still create empty database related variables.
    return [
        'DB_HOST' => '',
        'DB_USERNAME' => '',
        'DB_PASSWORD' => '',
        'DB_NAME' => '',
    ];
}

require $configPath;

// Can only run tests after intentionally adding configuration params (or via environment variable)
if (getenv('TEST_ENV') != 'codeception' && (empty($testEnvironment) || $testEnvironment != 'codeception')) {
    die('WARNING: Cannot run Codeception tests without defining the test environment in config.php'."\n");
}

// Format config into Codeception params
$params = [
    'DB_HOST' => $testDatabaseServer ?? $databaseServer ?? '',
    'DB_USERNAME' => $testDatabaseUsername ?? $databaseUsername ?? '',
    'DB_PASSWORD' => $testDatabasePassword ?? $databasePassword ?? '',
    'DB_NAME' => $testDatabaseName ?? $databaseName ?? '',
];

// Allow overrides of several environment variable only
// if certain variable is in the config.
if (isset($testPath)) {
    $params['ABSOLUTE_PATH'] = rtrim($testPath, '/');
}
if (isset($testURL)) {
    $params['ABSOLUTE_URL'] = rtrim($testURL, '/');
}

return $params;
