<?php
// Load the Gibbon config.php file and format it into Codeinception params
require '../config.php';

if (empty($testEnvironment) || $testEnvironment != 'codeception') {
    die('WARNING: Cannot run Codeception tests without defining the test environment in config.php'."\n");
}

return [
    'DB_HOST' => (isset($testDatabaseServer))? $testDatabaseServer : $databaseServer,
    'DB_USERNAME' => (isset($testDatabaseUsername))? $testDatabaseUsername : $databaseUsername,
    'DB_PASSWORD' => (isset($testDatabasePassword))? $testDatabasePassword: $databasePassword,
    'DB_NAME' => (isset($testDatabaseName))? $testDatabaseName: $databaseName,
    'ABSOLUTE_PATH' => (isset($testPath))? rtrim($testPath, '/') : 'localhost',
    'ABSOLUTE_URL' => (isset($testURL))? rtrim($testURL, '/') : 'http://localhost',
];
