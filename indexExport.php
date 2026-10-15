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
use Gibbon\Domain\System\LogGateway;
use Gibbon\Domain\FormGroups\FormGroupGateway;
use Gibbon\Domain\Students\StudentGateway;

require_once __DIR__ . '/gibbon.php';

$gibbonFormGroupID = $_GET['gibbonFormGroupID'] ?? '';
$URL = Url::fromRoute();

if (!$session->has('gibbonPersonID') || $session->get('gibbonRoleIDCurrentCategory') != 'Staff') {
    header("Location: {$URL->withReturn('error0')}");
    exit;
}

if ($gibbonFormGroupID == '') {
    header("Location: {$URL->withReturn('error1')}");
    exit;
}

$formGroupGateway = $container->get(FormGroupGateway::class);
$formGroup = $formGroupGateway->getByID($gibbonFormGroupID);

// Form group must exist in the current school year
if (empty($formGroup) || $formGroup['gibbonSchoolYearID'] != $session->get('gibbonSchoolYearID')) {
    header("Location: {$URL->withReturn('error3')}");
    exit;
}

$gibbonFormGroupID = $formGroup['gibbonFormGroupID'];

// View Form Groups_all can export any form group
$highestAction = getHighestGroupedAction($guid, '/modules/Form Groups/formGroups.php', $connection2);
$canViewAll = $highestAction == 'View Form Groups_all';

if (!$canViewAll) {
    // Only allow export for form groups this staff member is related to (tutor, EA, or enrolled)
    $related = $formGroupGateway->getFormGroupDetailsByID(
        $gibbonFormGroupID,
        $session->get('gibbonPersonID'),
        $session->get('gibbonSchoolYearID')
    );
    if ($related->isEmpty()) {
        header("Location: {$URL->withReturn('error0')}");
        exit;
    }
}

// Proceed!
$result = $container->get(StudentGateway::class)->selectStudentDetailsByFormGroup($gibbonFormGroupID);

$container->get(LogGateway::class)->addLog(
    $session->get('gibbonSchoolYearID'),
    'Form Groups',
    $session->get('gibbonPersonID'),
    'Form Group Export',
    ['gibbonFormGroupID' => $gibbonFormGroupID]
);

$exp = new Gibbon\Excel();
$exp->exportWithQuery($result, 'classList.xls');
