<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

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

use Gibbon\Url;
use Gibbon\View\Page;
use Gibbon\View\View;

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
$container->addServiceProvider(new Gibbon\Services\GoogleServiceProvider());

// Globals for backwards compatibility
$gibbon = $container->get('config');
$gibbon->locale = $container->get('locale');
$gibbon->session = $container->get('session');
$session = $container->get('session');
$guid = $gibbon->getConfig('guid');
$caching = $gibbon->getConfig('caching');
$version = $gibbon->getConfig('version');

// Handle Gibbon installation redirect
if (!$gibbon->isInstalled() && !$gibbon->isInstalling()) {
    header("Location: ./installer/install.php");
    exit;
}

// Autoload the current module namespace
if (!empty($gibbon->session->get('module'))) {
    $moduleNamespace = preg_replace('/[^a-zA-Z0-9]/', '', $gibbon->session->get('module'));
    $autoloader->addPsr4('Gibbon\\Module\\'.$moduleNamespace.'\\', realpath(__DIR__).'/modules/'.$gibbon->session->get('module').'/src');

    // Temporary backwards-compatibility for external modules (Query Builder)
    $autoloader->addPsr4('Gibbon\\'.$moduleNamespace.'\\', realpath(__DIR__).'/modules/'.$gibbon->session->get('module'));
    $autoloader->register(true);
}

// Initialize using the database connection
if ($gibbon->isInstalled() == true) {
    
    $mysqlConnector = new Gibbon\Database\MySqlConnector();
    if ($pdo = $mysqlConnector->connect($gibbon->getConfig())) {
        $container->add('db', $pdo);
        $container->share(Gibbon\Contracts\Database\Connection::class, $pdo);
        $connection2 = $pdo->getConnection();

        $gibbon->initializeCore($container);

        // Setup Url class baseUrl
        Url::setBaseUrl($gibbon->session->get('absoluteURL'));
    } else {
        // We need to handle failed database connections after install. Display an error if no connection 
        // can be established. Needs a specific error page once header/footer is split out of index.
        if (!$gibbon->isInstalling()) {
            $page = $container->get(Page::class)->setDefaults(__DIR__);
            $page->writeFromTemplate('error.twig.html', [
                'error' => sprintf(__('A database connection could not be established. Please %1$stry again%2$s.'), '', ''),
                'message' => ' ',
            ]);
            
            echo $page->render('index.twig.html');
            exit;
        }
    }
}
