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

use Gibbon\Services\Format;

// Gibbon system-wide include
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
} elseif ($page->isAddressValid($address, true) == false || stripos($address, 'modules') === false) {
    $page->addError(__('Illegal address detected: access denied.'));
} else {
    // Pass these globals into the script of the included file, for backwards compatibility.
    // These will be removed when we begin the process of ooifying action pages.
    $globals = [
        'guid'        => $guid,
        'gibbon'      => $gibbon,
        'version'     => $version,
        'session'     => $session,
        'pdo'         => $pdo,
        'connection2' => $connection2,
        'autoloader'  => $autoloader,
        'container'   => $container,
        'page'        => $page,
    ];

    if (is_file('./'.$address)) {
        $page->writeFromFile('./'.$address, $globals);

        $page->addData([
            'isLoggedIn'                     => $session->has('username') && $session->has('gibbonRoleIDCurrent'),
            'username'                       => $session->get('username'),
            'gibbonThemeName'                => $session->get('gibbonThemeName'),
            'organisationName'               => $session->get('organisationName'),
            'organisationNameShort'          => $session->get('organisationNameShort'),
            'organisationAdministratorName'  => $session->get('organisationAdministratorName'),
            'organisationAdministratorEmail' => $session->get('organisationAdministratorEmail'),
            'organisationLogo'               => $session->get('organisationLogo'),
            'time'                           => Format::time(date('H:i:s')),
            'date'                           => Format::date(date('Y-m-d')),
            'rightToLeft'                    => $session->get('i18n')['rtl'] == 'Y',
            'orientation'                    => $_GET['orientation'] ?? 'P',
            'hideHeader'                     => $_GET['hideHeader'] ?? false,
        ]);
    } else {
        $page->writeFromTemplate('error.twig.html');
    }
}

$page->addHeadExtra($session->get('analytics'));
$page->stylesheets->add('theme-dev', 'resources/assets/css/theme.min.css');
$page->stylesheets->add('core', 'resources/assets/css/core.min.css', ['weight' => 10]);

echo $page->render('report.twig.html');
