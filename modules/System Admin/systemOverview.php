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

    // Prepare and submit stats if that is what the system calls for
    if ($session->get('statsCollection') == 'Y') {
        $absolutePathProtocol = '';
        $absolutePath = '';
        if (substr($session->get('absoluteURL'), 0, 7) == 'http://') {
            $absolutePathProtocol = 'http';
            $absolutePath = substr($session->get('absoluteURL'), 7);
        } elseif (substr($session->get('absoluteURL'), 0, 8) == 'https://') {
            $absolutePathProtocol = 'https';
            $absolutePath = substr($session->get('absoluteURL'), 8);
        }

        $usersTotal = $pdo->selectOne("SELECT COUNT(*) FROM gibbonPerson");
        $usersFull = $pdo->selectOne("SELECT COUNT(*) FROM gibbonPerson WHERE status='Full'");

        echo "<iframe style='display: none; height: 10px; width: 10px' src='https://gibbonedu.org/services/tracker/tracker.php?absolutePathProtocol=".urlencode($absolutePathProtocol).'&absolutePath='.urlencode($absolutePath).'&organisationName='.urlencode($session->get('organisationName')).'&type='.urlencode($session->get('installType')).'&version='.urlencode($version).'&country='.$session->get('country')."&usersTotal=$usersTotal&usersFull=$usersFull'></iframe>";
    }

    $phpVersion = phpversion();
    $phpVersion = stripos($phpVersion, '-') !== false ? strstr($phpVersion, '-', true) : $phpVersion;

    $mysqlVersion = $pdo->selectOne("SELECT VERSION()");
    $mysqlVersion = stripos($mysqlVersion, '-') !== false ? strstr($mysqlVersion, '-', true) : $mysqlVersion;

    $phpRequirement = $gibbon->getSystemRequirement('php');
    $mysqlRequirement = $gibbon->getSystemRequirement('mysql');

    // Uploads folder check, make a request using a Guzzle HTTP get request
    $statusCheck = checkUploadsFolderStatus($session->get('absoluteURL'));
    if (!$statusCheck) {
        $uploadsCheck = Format::alert(__('The system check has detected that your uploads folder may be publicly accessible. This suggests a serious issue in your server configuration that should be addressed immediately. Please visit our {documentation} page for instructions to fix this issue.', [
            'documentation' => Format::link('https://docs.gibbonedu.org/administrators/getting-started/installing-gibbon/#post-install-server-config', __('Post-Install and Server Config')),
        ]), 'error');
    }

    echo $page->fetchFromTemplate('systemOverview.twig.html', [
        'gibbonVersion' => $version,
        'gibbonCheck'   => '',
        'phpVersion'    => $phpVersion,
        'phpCheck'      => version_compare($phpVersion, $phpRequirement, '>='),
        'mySqlVersion'  => $mysqlVersion,
        'mySqlCheck'    => version_compare($mysqlVersion, $mysqlRequirement, '>='),

        'versionCheck'  => getCurrentVersion($guid, $connection2, $version),
        'uploadsCheck'  => $uploadsCheck ?? '',

        'gibboneduComOrganisationName' => $session->get('gibboneduComOrganisationName'),
        'gibboneduComOrganisationKey'  => $session->get('gibboneduComOrganisationKey'),
    ]);

}
