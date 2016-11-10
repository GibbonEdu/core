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

    $http = (isset($_SERVER['HTTPS']))? 'https://' : 'http://';
    $port = ($_SERVER['SERVER_PORT'] != '80')? ':'.$_SERVER['SERVER_PORT'] : '';

    $pageURL = $http . $_SERVER['SERVER_NAME'] . $port . dirname($_SERVER['PHP_SELF']);
    $pageURL = rtrim($pageURL, '/ ').'/';

    define('GIBBON_URL', $pageURL);
}

require_once GIBBON_ROOT.'src/Autoloader.php';

$loader = new Autoloader( GIBBON_ROOT );

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

// Create objects for core classes
$session = new Gibbon\session( $guid );
$trans = new Gibbon\trans( $session );

