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

use Gibbon\Services\Module\Resource;
use Gibbon\Http\Url;
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;

require_once '../../gibbon.php';

$gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
$gibbonAdmissionsApplicationID = $_POST['gibbonAdmissionsApplicationID'] ?? '';
$gibbonAdmissionsAccountID = $_POST['gibbonAdmissionsAccountID'] ?? '';
$search = $_POST['search'] ?? '';
$tab = $_POST['tab'] ?? 0;

$URL = Url::fromModuleRoute('Admissions', 'applications_manage_edit')->withQueryParams(['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonAdmissionsApplicationID' => $gibbonAdmissionsApplicationID, 'search' => $search, 'tab' => $tab]);

if (isActionAccessible($guid, $connection2, Resource::fromRoute('Admissions', 'applications_manage_edit')) == false) {
    header("Location: {$URL->withReturn('error0')}");
    exit;
} else {
    // Proceed!

    // Get the application form data
    $admissionsApplicationGateway = $container->get(AdmissionsApplicationGateway::class);
    $application = $admissionsApplicationGateway->getByID($gibbonAdmissionsApplicationID);
    if (empty($gibbonAdmissionsApplicationID) || empty($application)) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
    }

    // Get the new admissions account
    $account = $container->get(AdmissionsAccountGateway::class)->getByID($gibbonAdmissionsAccountID);
    if (empty($account)) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
    }

    // Check that this application is associated with an account
    if ($application['foreignTable'] != 'gibbonAdmissionsAccount') {
        header("Location: {$URL->withReturn('error2')}");
        exit;
    }

    $updated = $admissionsApplicationGateway->update($gibbonAdmissionsApplicationID, [
        'foreignTableID' => $gibbonAdmissionsAccountID,
    ]);

    header("Location: {$URL->withReturn(!$updated ? 'error2' : 'success0')}");
}
