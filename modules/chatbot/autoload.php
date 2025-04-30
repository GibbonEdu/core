<?php
// Get absolute paths
$scriptPath = __DIR__;
$gibbonRoot = realpath($scriptPath . '/../../');

// Include Gibbon core autoloader first
require_once $gibbonRoot . '/vendor/autoload.php';

// Register module namespace
spl_autoload_register(function ($class) use ($scriptPath) {
    // Module namespace prefix
    $prefix = 'Gibbon\\Module\\ChatBot\\';
    $baseDir = $scriptPath . '/src/';

    // Check if the class uses the namespace prefix
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return; // Not our namespace, let other autoloaders handle it
    }

    // Get the relative class name
    $relativeClass = substr($class, $len);

    // Replace namespace separators with directory separators
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    } else {
        // Debug info
        error_log("Failed to autoload class: $class (File not found: $file)");
    }
});

// Check if we need to include Gibbon core files
if (!defined('GIBBONROOT')) {
    require_once $gibbonRoot . '/gibbon.php';
}

// Include module functions
require_once __DIR__ . '/moduleFunctions.php';

// Debug info
error_log("ChatBot module autoloader registered"); 