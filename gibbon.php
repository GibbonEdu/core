<?php
/**
 * @authur	Craig Rayner
 *
 * @version	13th April 2016
 *
 * @since	7th April 2016
 */

@session_start();

// Handle Gibbon installation
if (file_exists(dirname(__FILE__).'/config.php') == false) {
    // test if installer already invoked and ignore.
    if (false === strpos($_SERVER['PHP_SELF'], 'installer/install.php')) {
        $URL = './installer/install.php';
        header("Location: {$URL}");
    }
    exit();
}

if (!defined('GIBBON_ROOT')) {
    $path = dirname(__FILE__);
    $path = rtrim(str_replace('\\', '/', $path), '/');
    define('GIBBON_ROOT', $path);

    $http = (isset($_SERVER['HTTPS']))? 'https://' : 'http://';
    $port = ($_SERVER['SERVER_PORT'] != '80')? ':'.$_SERVER['SERVER_PORT'] : '';

    $pageURL = $http . $_SERVER['SERVER_NAME'] . $port . dirname($_SERVER['PHP_SELF']);
    $pageURL = rtrim($pageURL, '/ ');

    define('GIBBON_URL', $pageURL);
}

// Require the system-wide includes
require_once GIBBON_ROOT.'/config.php';
require_once GIBBON_ROOT.'/functions.php';
require_once GIBBON_ROOT.'/version.php';

// Setup the autoloader
require_once GIBBON_ROOT.'/src/Autoloader.php';

$loader = new Autoloader( GIBBON_ROOT );

$loader->addNameSpace('Gibbon\\', 'src/Gibbon');
$loader->addNameSpace('Library\\', 'src/Library');

$loader->register();

// New configuration object
$config = new Gibbon\config();

// New PDO DB connection
$pdo = new Gibbon\sqlConnection( $config );
$connection2 = $pdo->getConnection();

// Create the core objects
$session = new Gibbon\session( $config );
$trans = new Gibbon\trans( $pdo, $session );


?>