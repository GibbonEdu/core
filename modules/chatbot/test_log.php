<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Try multiple log locations
$logLocations = array(
    __DIR__ . '/debug.log',
    '/Applications/MAMP/logs/php_error.log',
    '/tmp/php_errors.log'
);

echo "<h1>Testing Multiple Log Locations</h1>";
foreach ($logLocations as $logFile) {
    echo "<h2>Testing log file: $logFile</h2>";
    
    // Try to set the error log location
    if (ini_set('error_log', $logFile) === false) {
        echo "<p style='color:red'>Failed to set error_log to: $logFile</p>";
    } else {
        echo "<p>Successfully set error_log to: $logFile</p>";
    }
    
    // Try to write to the log
    if (error_log("Test message for $logFile at " . date('Y-m-d H:i:s'))) {
        echo "<p style='color:green'>Successfully wrote test message to log</p>";
    } else {
        echo "<p style='color:red'>Failed to write test message to log</p>";
    }
    
    // Check file status
    echo "<pre>";
    echo "File exists: " . (file_exists($logFile) ? 'Yes' : 'No') . "\n";
    if (file_exists($logFile)) {
        echo "Is writable: " . (is_writable($logFile) ? 'Yes' : 'No') . "\n";
        echo "File permissions: " . substr(sprintf('%o', fileperms($logFile)), -4) . "\n";
        echo "File owner: " . fileowner($logFile) . "\n";
        echo "File size: " . filesize($logFile) . " bytes\n";
        echo "Last modified: " . date('Y-m-d H:i:s', filemtime($logFile)) . "\n";
    }
    echo "</pre>";
    
    // Force an error
    trigger_error("Test error message for $logFile", E_USER_WARNING);
}

// Display PHP configuration
echo "<h2>PHP Error Logging Configuration</h2>";
echo "<pre>";
echo "error_reporting: " . error_reporting() . "\n";
echo "display_errors: " . ini_get('display_errors') . "\n";
echo "log_errors: " . ini_get('log_errors') . "\n";
echo "error_log: " . ini_get('error_log') . "\n";
echo "Current user: " . get_current_user() . "\n";
echo "Script owner: " . fileowner(__FILE__) . "\n";
echo "PHP version: " . phpversion() . "\n";
echo "</pre>";

// Try direct file writing
echo "<h2>Testing Direct File Writing</h2>";
foreach ($logLocations as $logFile) {
    echo "<h3>Testing direct write to: $logFile</h3>";
    if ($fp = @fopen($logFile, 'a')) {
        if (fwrite($fp, "Direct write test at " . date('Y-m-d H:i:s') . "\n")) {
            echo "<p style='color:green'>Successfully wrote directly to file</p>";
        } else {
            echo "<p style='color:red'>Failed to write to file</p>";
        }
        fclose($fp);
    } else {
        echo "<p style='color:red'>Could not open file for writing</p>";
    }
}
?> 