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

$basePath = dirname(__FILE__);
$basePath = rtrim(str_replace('\\', '/', $basePath), '/');

// Handle Gibbon installation redirect
if (file_exists($basePath.'/config.php') == false || filesize($basePath.'/config.php') == 0) {
    // Test if installer already invoked and ignore.
    if (false === strpos($_SERVER['PHP_SELF'], 'installer/install.php')) {
        $URL = './installer/install.php';
        header("Location: {$URL}");
        exit();
    }
}

// Setup the composer autoloader
$autoloader = require_once $basePath.'/vendor/autoload.php';

// Require the system-wide functions
require_once $basePath.'/functions.php';


// Core Services
$container = new League\Container\Container();
$container->add('config', new Gibbon\Core($basePath));
$container->add('session', new Gibbon\Session($container));
$container->add('locale', new Gibbon\Locale($container));


// Set global config variables, for backwards compatability
$gibbon = $container->get('config');
$guid = $gibbon->guid();
$caching = $gibbon->getCaching();
$version = $gibbon->getVersion();


// Detect the current module - TODO: replace this logic when switching to routing.
$_SESSION[$guid]['address'] = isset($_GET['q'])? $_GET['q'] : '';
$_SESSION[$guid]['module'] = getModuleName($_SESSION[$guid]['address']);
$_SESSION[$guid]['action'] = getActionName($_SESSION[$guid]['address']);


// Autoload the current module namespace
if (!empty($_SESSION[$guid]['module'])) {
    $moduleNamespace = preg_replace('/[^a-zA-Z0-9]/', '', $_SESSION[$guid]['module']);
    $autoloader->addPsr4('Gibbon\\'.$moduleNamespace.'\\', $basePath.'/modules/'.$_SESSION[$guid]['module']);
    $autoloader->register(true);
}


if ($gibbon->isInstalled() == true) {

    // New PDO DB connection
    $container->add('db', new Gibbon\sqlConnection());

	$pdo = $container->get('db');
	$connection2 = $pdo->getConnection();

	// Initialize using the database connection
	$gibbon->initializeCore($container);
}
