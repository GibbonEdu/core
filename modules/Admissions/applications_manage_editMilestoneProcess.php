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
use Gibbon\Domain\Admissions\AdmissionsAccountGateway;
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;

require_once '../../gibbon.php';

$gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
$gibbonAdmissionsApplicationID = $_POST['gibbonAdmissionsApplicationID'] ?? '';
$search = $_POST['search'] ?? '';
$tab = $_POST['tab'] ?? 0;

$URL = Url::fromModuleRoute('Admissions', 'applications_manage_edit')->withQueryParams(['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonAdmissionsApplicationID' => $gibbonAdmissionsApplicationID, 'search' => $search, 'tab' => $tab]);

if (isActionAccessible($guid, $connection2, '/modules/Admissions/applications_manage_edit.php') == false) {
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

    // Get the admissions account
    $account = $container->get(AdmissionsAccountGateway::class)->getByID($application['foreignTableID']);
    if (empty($account)) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
    }

    $milestones = $_POST['milestones'] ?? [];
    $milestoneData = json_decode($application['milestones'] ?? '', true) ?? [];
    $milestonesNew = [];

    foreach ($milestones as $milestone => $value) {
        $milestonesNew[$milestone] = [
            'date'  => $milestoneData[$milestone]['date'] ?? date('Y-m-d H:i:s'),
            'user'  => $milestoneData[$milestone]['user'] ?? $session->get('gibbonPersonID'),
        ];
    }

    $updated = $admissionsApplicationGateway->update($gibbonAdmissionsApplicationID, [
        'milestones' => json_encode($milestonesNew),
    ]);

    header("Location: {$URL->withReturn(!$updated ? 'warning1' : 'success0')}");
}
