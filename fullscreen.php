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

//Gibbon system-wide includes
include './gibbon.php';

$session = $container->get('session');

//Check to see if system settings are set from databases
if (empty($session->get('systemSettingsSet'))) {
    getSystemSettings($guid, $connection2);
}

//If still false, show warning, otherwise display page
if (empty($session->get('systemSettingsSet'))) {
    exit(__($guid, 'System Settings are not set: the system cannot be displayed'));
}

$page = $container->get('page');

$contents = '';

if (empty($page->getAddress())) {
    $page->addWarning(__('There is no content to display'));
} elseif (stripos($page->getAddress(), '..') !== false) {
    $page->addError(__('Illegal address detected: access denied.'));
} else {
    if (is_file('./'.$page->getAddress())) {
        ob_start();
        include './'.$page->getAddress();
        $contents = ob_get_contents();
        ob_end_clean();
    } else {
        ob_start();
        include './error.php';
        $contents = ob_get_contents();
        ob_end_clean();
    }
}

$twig = $container->get('twig');
$page = $container->get('page');

$data = [
    'page' => $page->gatherData(),
    'contents' => $contents,
];

echo $twig->render('base.twig.html', $data);
