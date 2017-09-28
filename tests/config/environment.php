<?php
// Attempt to load the Gibbon config.php file
$configPath = __DIR__ . '/../../config.php';
if (!file_exists($configPath)) {
    return ['DB_HOST' => '','DB_USERNAME' => '','DB_PASSWORD' => '','DB_NAME' => '','ABSOLUTE_PATH' => '','ABSOLUTE_URL' => ''];
}

require $configPath;

// Can't run tests unless intentionally adding configuration params (or via Travis CI)
if (getenv('TEST_ENV') != 'codeception' && (empty($testEnvironment) || $testEnvironment != 'codeception')) {
    die('WARNING: Cannot run Codeception tests without defining the test environment in config.php'."\n");
}

// Format config into Codeception params
return [
    'DB_HOST' => (isset($testDatabaseServer))? $testDatabaseServer : $databaseServer,
    'DB_USERNAME' => (isset($testDatabaseUsername))? $testDatabaseUsername : $databaseUsername,
    'DB_PASSWORD' => (isset($testDatabasePassword))? $testDatabasePassword: $databasePassword,
    'DB_NAME' => (isset($testDatabaseName))? $testDatabaseName: $databaseName,
    'ABSOLUTE_PATH' => (isset($testPath))? rtrim($testPath, '/') : 'localhost',
    'ABSOLUTE_URL' => (isset($testURL))? rtrim($testURL, '/') : 'http://127.0.0.1:8888',
];
