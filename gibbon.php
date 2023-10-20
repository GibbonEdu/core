<?php
/*
Gibbon: the flexible, open school platform
Founded by Ross Parker at ICHK Secondary. Built by Ross Parker, Sandra Kuipers and the Gibbon community (https://gibbonedu.org/about/)
Copyright © 2010, Gibbon Foundation
Gibbon™, Gibbon Education Ltd. (Hong Kong)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Http\Url;

// Handle fatal errors more gracefully
register_shutdown_function(function () {
    $lastError = error_get_last();
    if ($lastError && ($lastError['type'] === E_ERROR || $lastError['type'] === E_CORE_ERROR || $lastError['type'] === E_COMPILE_ERROR) ) {
        include __DIR__.'/error.php';
    }
    exit;
});

// Check for the autoloader file
if (!file_exists(__DIR__.'/vendor/autoload.php')) {
    $message = 'Fatal Error: Missing composer autoloader. Your vendor folder is likely not installed. If you are running cutting edge code, navigate to your base dir in a terminal window and run the "composer install" command. See the Cutting Edge Code documentation for more information: https://docs.gibbonedu.org/administrators/getting-started/installing-gibbon/cutting-edge-code/';
    include __DIR__.'/error.php';
    exit;
}

// Setup the composer autoloader
$autoloader = require_once __DIR__.'/vendor/autoload.php';

// Require the system-wide functions
require_once __DIR__.'/functions.php';

// Core Services
$container = new League\Container\Container();
$container->delegate(new League\Container\ReflectionContainer);
$container->add('autoloader', $autoloader);

$container->inflector(\League\Container\ContainerAwareInterface::class)
          ->invokeMethod('setContainer', [$container]);

$container->inflector(\Gibbon\Services\BackgroundProcess::class)
          ->invokeMethod('setProcessor', [\Gibbon\Services\BackgroundProcessor::class]);

$container->addServiceProvider(new Gibbon\Services\CoreServiceProvider(__DIR__));
$container->addServiceProvider(new Gibbon\Services\ViewServiceProvider());
$container->addServiceProvider(new Gibbon\Services\AuthServiceProvider());

// Globals for backwards compatibility
$gibbon = $container->get('config');
$gibbon->locale = $container->get('locale');
$guid = $gibbon->getConfig('guid');
$caching = $gibbon->getConfig('caching');
$version = $gibbon->getConfig('version');

// Handle Gibbon installation redirect
if (!$gibbon->isInstalled() && !$gibbon->isInstalling()) {
    define('SESSION_TABLE_AVAILABLE', false);
    header("Location: ./installer/install.php");
    exit;
}

// Initialize the database connection
if ($gibbon->isInstalled()) {
    $mysqlConnector = new Gibbon\Database\MySqlConnector();

    // Display a static error message for database connections after install.
    if ($pdo = $mysqlConnector->connect($gibbon->getConfig())) {
        // Add the database to the container
        $connection2 = $pdo->getConnection();
        $container->add('db', $pdo);
        $container->share(Gibbon\Contracts\Database\Connection::class, $pdo);

        // Add a feature flag here to prevent errors before updating
        // TODO: this can likely be removed in v24+
        if (!defined('SESSION_TABLE_AVAILABLE')) {
            $hasSessionTable = $pdo->selectOne("SHOW TABLES LIKE 'gibbonSession'");
            define('SESSION_TABLE_AVAILABLE', !empty($hasSessionTable));
        }

        // Initialize core
        try {
            $gibbon->initializeCore($container);
        } catch (\Exception $e) {
            $message = __('Configuration Error: there is a problem accessing the current Academic Year from the database.');
            include __DIR__.'/error.php';
            exit;
        }
        
    } else {
        if (!$gibbon->isInstalling()) {
            $message = sprintf(__('A database connection could not be established. Please %1$stry again%2$s.'), '', '');
            include __DIR__.'/error.php';
            exit;
        }
    }
}

if (!defined('SESSION_TABLE_AVAILABLE')) {
    define('SESSION_TABLE_AVAILABLE', false);
}

// Globals for backwards compatibility
$session = $container->get('session');
$gibbon->session = $session;
$container->share(\Gibbon\Contracts\Services\Session::class, $session);

// Setup global absoluteURL for all urls.
if ($gibbon->isInstalled() && $session->has('absoluteURL')) {
    Url::setBaseUrl($session->get('absoluteURL'));
} else {
    // TODO: put this absoluteURL detection somewhere?
    $absoluteURL = (function () {
        // Find out the base installation URL path.
        $prefixLength = strlen(realpath($_SERVER['DOCUMENT_ROOT']));
        $baseDir = realpath(__DIR__) . '/';
        $urlBasePath = substr($baseDir, $prefixLength);

        // Construct the full URL to the base URL path.
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $protocol = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
        return "{$protocol}://{$host}{$urlBasePath}";
    })();
    Url::setBaseUrl($absoluteURL);
}

// Autoload the current module namespace
if (!empty($session->get('module'))) {
    $moduleNamespace = preg_replace('/[^a-zA-Z0-9]/', '', $session->get('module'));
    $autoloader->addPsr4('Gibbon\\Module\\'.$moduleNamespace.'\\', realpath(__DIR__).'/modules/'.$session->get('module').'/src');
    $autoloader->register(true);
}

// Sanitize incoming user-supplied GET variables
$_GET = $container->get(\Gibbon\Data\Validator::class)->sanitizeUrlParams($_GET);
