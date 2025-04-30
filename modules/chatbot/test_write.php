<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test multiple locations
$locations = [
    __DIR__ . '/test_write.log',
    __DIR__ . '/../test_write.log',
    '/Applications/MAMP/logs/test_write.log',
    '/tmp/test_write.log'
];

$test_message = "Test write at " . date('Y-m-d H:i:s') . "\n";

foreach ($locations as $location) {
    echo "Testing write to: $location\n";
    
    // Test 1: file_put_contents
    try {
        $result = file_put_contents($location, $test_message, FILE_APPEND);
        if ($result === false) {
            echo "file_put_contents failed. Error: " . error_get_last()['message'] . "\n";
        } else {
            echo "file_put_contents succeeded. Bytes written: $result\n";
        }
    } catch (Exception $e) {
        echo "file_put_contents exception: " . $e->getMessage() . "\n";
    }
    
    // Test 2: fopen/fwrite
    try {
        $fp = fopen($location, 'a');
        if ($fp === false) {
            echo "fopen failed. Error: " . error_get_last()['message'] . "\n";
        } else {
            $bytes = fwrite($fp, $test_message);
            if ($bytes === false) {
                echo "fwrite failed. Error: " . error_get_last()['message'] . "\n";
            } else {
                echo "fwrite succeeded. Bytes written: $bytes\n";
            }
            fclose($fp);
        }
    } catch (Exception $e) {
        echo "fopen/fwrite exception: " . $e->getMessage() . "\n";
    }
    
    // Test 3: error_log
    try {
        $result = error_log($test_message, 3, $location);
        if ($result === false) {
            echo "error_log failed. Error: " . error_get_last()['message'] . "\n";
        } else {
            echo "error_log succeeded\n";
        }
    } catch (Exception $e) {
        echo "error_log exception: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Show current user and permissions
echo "Current user: " . get_current_user() . "\n";
echo "Process user: " . posix_getpwuid(posix_geteuid())['name'] . "\n";
echo "Process group: " . posix_getgrgid(posix_getegid())['name'] . "\n";

// Show directory permissions
echo "\nDirectory permissions:\n";
foreach ($locations as $location) {
    $dir = dirname($location);
    if (file_exists($dir)) {
        echo "$dir: " . substr(sprintf('%o', fileperms($dir)), -4) . "\n";
    }
}

// Show file permissions
echo "\nFile permissions:\n";
foreach ($locations as $location) {
    if (file_exists($location)) {
        echo "$location: " . substr(sprintf('%o', fileperms($location)), -4) . "\n";
    }
}
?> 