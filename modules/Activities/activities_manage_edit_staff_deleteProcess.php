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

use Gibbon\Domain\Activities\ActivityStaffGateway;

include '../../gibbon.php';

$gibbonActivityID = $_POST['gibbonActivityID'] ?? '';
$gibbonActivityStaffID = $_POST['gibbonActivityStaffID'] ?? '';
$search = $_POST['search'] ?? '';
$gibbonSchoolYearTermID = $_POST['gibbonSchoolYearTermID'] ?? '';

$URL = $session->get('absoluteURL') . '/index.php?q=/modules/' . $session->get('module') . "/activities_manage_edit.php&gibbonActivityID=$gibbonActivityID&search=$search&gibbonSchoolYearTermID=$gibbonSchoolYearTermID";

if (isActionAccessible($guid, $connection2, '/modules/Activities/activities_manage_edit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    $activityStaffGateway = $container->get(ActivityStaffGateway::class);

    if (!$activityStaffGateway->exists($gibbonActivityStaffID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        if (!$activityStaffGateway->delete($gibbonActivityStaffID)) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit();
        }

        $URL .= '&return=success0';
        header("Location: {$URL}");
    }
}
