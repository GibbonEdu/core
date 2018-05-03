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

// Setup the composer autoloader
$autoloader = require_once __DIR__.'/vendor/autoload.php';

// Require the system-wide functions
require_once __DIR__.'/functions.php';

// Core Services
$container = new League\Container\Container();
$container->add('config', new Gibbon\Core(__DIR__));
$container->add('session', new Gibbon\Session($container));
$container->add('locale', new Gibbon\Locale($container));
$container->add('autoloader', $autoloader);

// Globals for backwards compatibility
$gibbon = $container->get('config');
$gibbon->session = $container->get('session');
$gibbon->locale = $container->get('locale');
$guid = $gibbon->guid();
$caching = $gibbon->getCaching();
$version = $gibbon->getVersion();

// Handle Gibbon installation redirect
if (!$gibbon->isInstalled() && stripos($_SERVER['PHP_SELF'], 'installer/install.php') === false) {
    header("Location: ./installer/install.php");
    exit;
}

// Autoload the current module namespace
if (!empty($gibbon->session->get('module'))) {
    $moduleNamespace = preg_replace('/[^a-zA-Z0-9]/', '', $gibbon->session->get('module'));
    $autoloader->addPsr4('Gibbon\\'.$moduleNamespace.'\\', realpath(__DIR__).'/modules/'.$gibbon->session->get('module'));
    $autoloader->register(true);
}

// Initialize using the database connection
if ($gibbon->isInstalled() == true) {
    $container->add('db', new Gibbon\sqlConnection());

    $pdo = $container->get('db');
    $connection2 = $pdo->getConnection();

    $gibbon->initializeCore($container);
}