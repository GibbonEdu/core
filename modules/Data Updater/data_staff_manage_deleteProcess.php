<?php

use Gibbon\Domain\DataUpdater\StaffUpdateGateway;
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

include '../../gibbon.php';

$gibbonSchoolYearID = isset($_POST['gibbonSchoolYearID'])? $_POST['gibbonSchoolYearID'] : $_SESSION[$guid]['gibbonSchoolYearID'];
$gibbonStaffUpdateID = $_POST['gibbonStaffUpdateID'];
$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/data_staff_manage_delete.php&gibbonStaffUpdateID=$gibbonStaffUpdateID&gibbonSchoolYearID=$gibbonSchoolYearID";
$URLDelete = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address']).'/data_staff_manage.php&gibbonSchoolYearID='.$gibbonSchoolYearID;

if (isActionAccessible($guid, $connection2, '/modules/Data Updater/data_staff_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    // Check required values
    if (empty($gibbonStaffUpdateID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        return;
    } 

    $staffUpdateGateway = $container->get(StaffUpdateGateway::class);

    // Check database records exist
    $values = $staffUpdateGateway->getByID($gibbonStaffUpdateID);
    if (!$staffUpdateGateway->exists($gibbonStaffUpdateID)) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        return;
    }

    $deleted = $staffUpdateGateway->delete($gibbonStaffUpdateID);

    if (!$deleted) {
        $URL .= '&return=error2';
        header("Location: {$URL}");
        return;
    }

    $URLDelete .= '&return=success0';
    header("Location: {$URLDelete}");
}
