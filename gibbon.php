<?php
/**
 * @authur	Craig Rayner
 *
 * @version	13th April 2016
 *
 * @since	7th April 2016
 */
require_once dirname(__FILE__).'/functions.php';

if (!defined('GIBBON_ROOT')) {
    $dr = dirname(__FILE__);
    $dr = rtrim(str_replace('\\', '/', $dr), '/');
    define('GIBBON_ROOT', $dr.'/');

    $pageURL = 'http';
    if (isset($_SERVER['HTTPS'])) {
        $pageURL .= 's';
    }
    $pageURL .= '://';
    if ($_SERVER['SERVER_PORT'] != '80') {
        $pageURL .= $_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'].dirname($_SERVER['PHP_SELF']);
    } else {
        $pageURL .= $_SERVER['SERVER_NAME'].dirname($_SERVER['PHP_SELF']);
    }
    $pageURL = rtrim($pageURL, '/ ').'/';
    define('GIBBON_URL', $pageURL);
}

if (!defined('GIBBON_ROOT')) {
    define('GIBBON_ROOT', str_replace(array('/src', '\\src'), '', dirname(__FILE__)).'/');
}

require_once GIBBON_ROOT.'src/Autoloader.php';

$loader = new Autoloader();

$loader->addNameSpace('Gibbon\\', 'src/Gibbon');
$loader->addNameSpace('Library\\', 'src/Library');

$loader->register();

if (file_exists(GIBBON_ROOT.'config.php')) {
    include GIBBON_ROOT.'config.php';
} else {
    if (false === strpos($_SERVER['PHP_SELF'], 'installer/install.php')) {
        // test if installer already invoked and ignore.

        $URL = GIBBON_URL.'installer/install.php';
        header("Location: {$URL}");
    }
}

$session = new Gibbon\session();


