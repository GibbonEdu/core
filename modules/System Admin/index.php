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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Services\Format;

//Module includes
require_once __DIR__ . '/moduleFunctions.php';

if (isActionAccessible($guid, $connection2, '/modules/System Admin/systemSettings.php') == false) {
    // Access denied
    echo Format::alert(__('You do not have access to this action.'));
} else {

    // Proceed!
    $page->breadcrumbs->add(__('System Overview'));

    $phpVersion = phpversion();
    $mysqlVersion = $pdo->selectOne("SELECT VERSION()");

    $phpRequirement = $gibbon->getSystemRequirement('php');
    $mysqlRequirement = $gibbon->getSystemRequirement('mysql');

    echo $page->fetchFromTemplate('systemAdmin.twig.html', [
        'gibbonVersion' => $version,
        'gibbonCheck'   => '',
        'phpVersion'    => $phpVersion,
        'phpCheck'      => version_compare($phpVersion, $phpRequirement, '>='),
        'mySqlVersion'  => $mysqlVersion,
        'mySqlCheck'    => version_compare($mysqlVersion, $mysqlRequirement, '>='),

        'versionCheck' => getCurrentVersion($guid, $connection2, $version),

        'gibboneduComOrganisationName' => $gibbon->session->get('gibboneduComOrganisationName'),
        'gibboneduComOrganisationKey'  => $gibbon->session->get('gibboneduComOrganisationKey'),
    ]);

}
