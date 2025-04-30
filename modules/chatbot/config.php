<?php
// Database Settings
$databaseServer = 'localhost';
$databasePort = '8889';
$databaseName = 'chhs';
$databaseUsername = 'admin';
$databasePassword = '123JUBani';
$databaseSocket = '/Applications/MAMP/tmp/mysql/mysql.sock';

// Set database connection string
$databaseConnectionString = "mysql:unix_socket=$databaseSocket;dbname=$databaseName";

// Error Reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Set timezone
date_default_timezone_set('America/Los_Angeles');

// Debug mode
$debugMode = true;

// Log database connection attempt
error_log("Database Configuration:");
error_log("Server: $databaseServer");
error_log("Port: $databasePort");
error_log("Database: $databaseName");
error_log("Username: $databaseUsername");
error_log("Socket: $databaseSocket");

// Set include path
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__); 