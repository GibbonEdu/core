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

include '../../gibbon.php';

$_POST = $container->get(Validator::class)->sanitize($_POST);

$gibbonStaffID = $_GET['gibbonStaffID'] ?? '';
$gibbonPersonID = $_GET['gibbonPersonID'] ?? '';
$search = $_GET['search'] ?? '';

if ($gibbonStaffID == '' or $gibbonPersonID == '') { echo 'Fatal error loading this page!';
} else {
    $URL = $session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_POST['address'])."/staff_manage_edit_facility_add.php&gibbonPersonID=$gibbonPersonID&gibbonStaffID=$gibbonStaffID&search=$search";

    if (isActionAccessible($guid, $connection2, '/modules/Staff/staff_manage_edit_facility_add.php') == false) {
        $URL .= '&return=error0';
        header("Location: {$URL}");
    } else {
        //Proceed!
        //Check if person specified
        if ($gibbonStaffID == '' or $gibbonPersonID == '') {
            $URL .= '&return=error1';
            header("Location: {$URL}");
        } else {
            try {
                $data = array('gibbonStaffID' => $gibbonStaffID, 'gibbonPersonID' => $gibbonPersonID);
                $sql = 'SELECT gibbonStaff.*, preferredName, surname FROM gibbonStaff JOIN gibbonPerson ON (gibbonStaff.gibbonPersonID=gibbonPerson.gibbonPersonID) WHERE gibbonStaffID=:gibbonStaffID AND gibbonPerson.gibbonPersonID=:gibbonPersonID';
                $result = $connection2->prepare($sql);
                $result->execute($data);
            } catch (PDOException $e) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
                exit();
            }

            if ($result->rowCount() != 1) {
                $URL .= '&return=error2';
                header("Location: {$URL}");
            } else {
                $gibbonSpaceID = $_POST['gibbonSpaceID'] ?? '';
                $usageType = $_POST['usageType'] ?? '';

                if ($gibbonSpaceID == '') {
                    $URL .= '&return=error1&step=1';
                    header("Location: {$URL}");
                } else {
                    //Write to database
                    try {
                        $data = array('gibbonPersonID' => $gibbonPersonID, 'gibbonSpaceID' => $gibbonSpaceID, 'usageType' => $usageType);
                        $sql = 'INSERT INTO gibbonSpacePerson SET gibbonPersonID=:gibbonPersonID, gibbonSpaceID=:gibbonSpaceID, usageType=:usageType';
                        $result = $connection2->prepare($sql);
                        $result->execute($data);
                    } catch (PDOException $e) {
                        $URL .= '&return=error2';
                        header("Location: {$URL}");
                        exit();
                    }

                    $URL .= "&return=success0";
                    header("Location: {$URL}");
                }
            }
        }
    }
}
