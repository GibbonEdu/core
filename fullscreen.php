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

// Gibbon system-wide includes
require_once './gibbon.php';

// Setup the Page and Session objects
$page = $container->get('page');
$session = $container->get('session');

// Check to see if system settings are set from databases
if (!$session->has('systemSettingsSet')) {
    getSystemSettings($guid, $connection2);
}

// If still false, show warning, otherwise display page
if (!$session->has('systemSettingsSet')) {
    exit(__('System Settings are not set: the system cannot be displayed'));
}

$address = $page->getAddress();

if (empty($address)) {
    $page->addWarning(__('There is no content to display'));
} elseif (stripos($address, '..') !== false) {
    $page->addError(__('Illegal address detected: access denied.'));
} else {
    if (is_file('./'.$address)) {
        $page->writeFromFile('./'.$address);
    } else {
        $page->writeFromFile('./error.php');
    }
}

echo $page->render('fullscreen.twig.html');
