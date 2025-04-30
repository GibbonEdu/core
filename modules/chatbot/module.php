<?php
// /Applications/MAMP/htdocs/chhs/modules/ChatBot/module.php

// Define a path constant for this module
define('MODULE_PATH', __DIR__);

// Get the absolute path to Gibbon root directory
$gibbonRoot = realpath(__DIR__ . '/../../');

// Include autoloader if not already included
if (!class_exists('\Gibbon\Module\ChatBot\DeepSeekAPI')) {
    require_once __DIR__ . '/autoload.php';
} else {
    // Gibbon's bootstrap file if autoloader already handled
    require_once $gibbonRoot . '/gibbon.php';
}

// Module initialization is complete
return true;