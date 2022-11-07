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
use Gibbon\Domain\Admissions\AdmissionsApplicationGateway;

require_once '../../gibbon.php';

$gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID');
$gibbonAdmissionsApplicationID = $_REQUEST['gibbonAdmissionsApplicationID'] ?? '';
$search = $_REQUEST['search'] ?? '';

$URL = Url::fromModuleRoute('Admissions', 'applications_manage')->withQueryParams(['gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonAdmissionsApplicationID' => $gibbonAdmissionsApplicationID, 'search' => $search]);

if (isActionAccessible($guid, $connection2, Resource::fromRoute('Admissions', 'applications_manage_reject')) == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Proceed!
    $admissionsApplicationGateway = $container->get(AdmissionsApplicationGateway::class);

    if (empty($gibbonAdmissionsApplicationID)) {
        header("Location: {$URL->withReturn('error1')}");
        exit;
    }

    $application = $admissionsApplicationGateway->getByID($gibbonAdmissionsApplicationID);
    if (empty($application)) {
        header("Location: {$URL->withReturn('error2')}");
        exit;
    }

    $rejected = $admissionsApplicationGateway->update($gibbonAdmissionsApplicationID, ['status' => 'Rejected']);

    $URL .= !$rejected
        ? '&return=error2'
        : '&return=success0';

    header("Location: {$URL}");
}
