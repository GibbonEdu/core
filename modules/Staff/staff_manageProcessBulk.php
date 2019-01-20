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

include '../../gibbon.php';

$action = isset($_POST['action']) ? $_POST['action'] : '';
$gibbonStaffID = isset($_POST['gibbonStaffID']) ? $_POST['gibbonStaffID'] : array();
$dateEnd = isset($_POST['dateEnd']) ? dateConvert($guid, $_POST['dateEnd']) : date('Y-m-d');

$allStaff = isset($_GET['allStaff']) ? $_GET['allStaff'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_POST['address'])."/staff_manage.php&search=$search&allStaff=$allStaff";

if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
} else {
    //Proceed!
    if (empty($action) || empty($gibbonStaffID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
    } else {
        $gibbonStaffIDList = is_array($gibbonStaffID)? implode(',', $gibbonStaffID) : $gibbonStaffID;

        if ($action == 'Left') {
            $data = array('gibbonStaffIDList' => $gibbonStaffIDList, 'dateEnd' => $dateEnd);
            $sql = "UPDATE gibbonStaff 
                    JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=gibbonStaff.gibbonPersonID) 
                    SET gibbonPerson.status='Left', gibbonPerson.dateEnd=:dateEnd
                    WHERE FIND_IN_SET(gibbonStaffID, :gibbonStaffIDList)";

            $updated = $pdo->update($sql, $data);

            if ($pdo->getQuerySuccess() == false){
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }
        }

        $URL .= '&return=success0';
        header("Location: {$URL}");
        exit();
    }
}
