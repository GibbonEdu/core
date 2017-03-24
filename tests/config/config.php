<?php
// Load the Gibbon config.php file and format it into Codeinception params
require '../config.php';
return [
    'DB_HOST' => $databaseServer,
    'DB_USERNAME' => $databaseUsername,
    'DB_PASSWORD' => $databasePassword,
    'DB_NAME' => $databaseName,
];
