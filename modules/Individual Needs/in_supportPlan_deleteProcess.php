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

use Gibbon\Data\Validator;
use Gibbon\Contracts\Filesystem\FileHandler;
use Gibbon\Domain\IndividualNeeds\StudentSupportPlanGateway;

require_once '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonPersonID = $_POST['gibbonPersonID'] ?? '';
$gibbonStudentSupportPlanID = $_POST['gibbonStudentSupportPlanID'] ?? '';
$URL = $session->get('absoluteURL').'/index.php?q=/modules/Individual Needs/in_supportPlan_manage.php&gibbonPersonID='.urlencode($gibbonPersonID);

if (isActionAccessible($guid, $connection2, '/modules/Individual Needs/in_supportPlan_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
}

if (empty($gibbonStudentSupportPlanID)) {
    $URL .= '&return=error1';
    header("Location: {$URL}");
    exit;
}

$planGateway = $container->get(StudentSupportPlanGateway::class);
$plan = $planGateway->getByID($gibbonStudentSupportPlanID);

if (empty($plan) || $plan['gibbonPersonID'] != $gibbonPersonID) {
    $URL .= '&return=error2';
    header("Location: {$URL}");
    exit;
}

// Delete physical file if type is File
if ($plan['type'] == 'File' && !empty($plan['filePath'])) {
    $container->get(FileHandler::class)->deleteFile('gibbonStudentSupportPlan', $gibbonStudentSupportPlanID, 'filePath');
}

$deleted = $planGateway->delete($gibbonStudentSupportPlanID);

if (!$deleted) {
    $URL .= '&return=error2';
} else {
    $URL .= '&return=success0';
}

header("Location: {$URL}");
exit;
